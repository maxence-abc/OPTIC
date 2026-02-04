<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/establishments')]
final class EstablishmentPageController extends AbstractController
{
    #[Route('/{id}', name: 'front_establishment_show', methods: ['GET'])]
    public function show(
        Establishment $establishment,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $serviceId = $request->query->getInt('service');
        $dateStr   = (string) $request->query->get('date'); // YYYY-MM-DD
        $timeStr   = (string) $request->query->get('time'); // HH:mm
        $weekStr   = (string) $request->query->get('week'); // YYYY-MM-DD

        // ✅ Image hero depuis /public/uploads/establishments/{id}/...
        $heroSrc = $this->findHeroImageForEstablishment((int) $establishment->getId());

        // Service sélectionné (doit appartenir à l'établissement)
        $selectedService = null;
        if ($serviceId > 0) {
            $candidate = $em->getRepository(Service::class)->find($serviceId);
            if ($candidate && $candidate->getEstablishment()?->getId() === $establishment->getId()) {
                $selectedService = $candidate;
            }
        }

        // Semaine (lundi)
        $weekStart = $this->weekStart($weekStr ?: $dateStr ?: 'today');

        // Jours ouverts uniquement
        $openWeekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $d = (clone $weekStart)->modify("+{$i} days");
            if ($this->isOpenOnDate($establishment, $d)) {
                $openWeekDays[] = $d;
            }
        }

        // Date sélectionnée
        $selectedDate = null;
        if ($dateStr) {
            try {
                $selectedDate = new \DateTime($dateStr);
                $selectedDate->setTime(0, 0, 0);
            } catch (\Throwable $e) {
                $selectedDate = null;
            }
        }

        // Si date absente/fermée => premier jour ouvert
        if (!$selectedDate || !$this->isOpenOnDate($establishment, $selectedDate)) {
            $selectedDate = !empty($openWeekDays) ? (clone $openWeekDays[0])->setTime(0, 0, 0) : null;
        }

        // Créneaux
        $slots = [];
        if ($selectedService && $selectedDate) {
            $slots = $this->generateAvailableSlots($em, $establishment, $selectedService, $selectedDate);
        }

        return $this->render('establishment_page/show.html.twig', [
            'establishment'     => $establishment,
            'heroSrc'           => $heroSrc,

            'selectedService'   => $selectedService,
            'selectedServiceId' => $selectedService?->getId(),
            'weekStart'         => $weekStart,
            'openWeekDays'      => $openWeekDays,
            'selectedDate'      => $selectedDate,
            'selectedDateStr'   => $selectedDate ? $selectedDate->format('Y-m-d') : null,
            'selectedTime'      => $timeStr ?: null,
            'slots'             => $slots,
        ]);
    }

    #[Route('/{id}/book', name: 'front_establishment_book', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function book(
        Establishment $establishment,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('book_appointment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        $serviceId = (int) $request->request->get('service');
        $dateStr   = (string) $request->request->get('date');
        $timeStr   = (string) $request->request->get('time');

        $service = $em->getRepository(Service::class)->find($serviceId);
        if (!$service || $service->getEstablishment()?->getId() !== $establishment->getId()) {
            $this->addFlash('error', 'Service invalide.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        try {
            $date = new \DateTime($dateStr);
            $date->setTime(0, 0, 0);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Date invalide.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        if (!$this->isOpenOnDate($establishment, $date)) {
            $this->addFlash('error', 'Établissement fermé ce jour-là.');
            return $this->redirectToRoute('front_establishment_show', [
                'id' => $establishment->getId(),
                'service' => $service->getId(),
            ]);
        }

        $professional = $establishment->getOwner();
        if (!$professional instanceof User) {
            $this->addFlash('error', 'Aucun professionnel associé.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        if (!str_contains($timeStr, ':')) {
            $this->addFlash('error', 'Heure invalide.');
            return $this->redirectToRoute('front_establishment_show', [
                'id' => $establishment->getId(),
                'service' => $service->getId(),
                'date' => $date->format('Y-m-d'),
            ]);
        }

        // Re-check slots
        $slots = $this->generateAvailableSlots($em, $establishment, $service, $date);
        $slotOk = false;
        foreach ($slots as $s) {
            if ($s['time'] === $timeStr) { $slotOk = true; break; }
        }
        if (!$slotOk) {
            $this->addFlash('error', 'Ce créneau n’est plus disponible.');
            return $this->redirectToRoute('front_establishment_show', [
                'id' => $establishment->getId(),
                'service' => $service->getId(),
                'date' => $date->format('Y-m-d'),
            ]);
        }

        [$h, $m] = array_map('intval', explode(':', $timeStr));
        $start = (clone $date)->setTime($h, $m, 0);

        $duration = (int) $service->getDuration();
        $buffer   = (int) ($service->getBufferTime() ?? 0);

        $end = (clone $start)->modify("+{$duration} minutes");
        if ($buffer > 0) {
            $end = (clone $end)->modify("+{$buffer} minutes");
        }

        $appointment = new Appointment();
        $appointment->setClient($this->getUser());
        $appointment->setProfessional($professional);
        $appointment->setService($service);
        $appointment->setDate($date);
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $appointment->setStatus('pending');
        $appointment->setCreatedAt(new \DateTimeImmutable());

        $em->persist($appointment);
        $em->flush();

        $this->addFlash('success', 'Réservation confirmée ✅');

        return $this->redirectToRoute('front_establishment_show', [
            'id' => $establishment->getId(),
            'service' => $service->getId(),
            'date' => $date->format('Y-m-d'),
        ]);
    }

    private function findHeroImageForEstablishment(int $establishmentId): string
    {
        // Si tu as pas de placeholder, tu peux laisser vide ou mettre une image existante
        $fallback = '/images/placeholder-establishment.jpg';

        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/establishments/' . $establishmentId;
        if (!is_dir($dir)) {
            return $fallback;
        }

        $finder = new Finder();
        $finder->files()
            ->in($dir)
            ->depth('== 0')
            ->name('/\.(jpe?g|png|webp)$/i')
            ->sortByName();

        foreach ($finder as $file) {
            return '/uploads/establishments/' . $establishmentId . '/' . $file->getFilename();
        }

        return $fallback;
    }

    private function weekStart(string $dateStr): \DateTime
    {
        try { $d = new \DateTime($dateStr); }
        catch (\Throwable $e) { $d = new \DateTime('today'); }

        $d->setTime(0, 0, 0);
        $isoDay = (int) $d->format('N');
        return $d->modify('-' . ($isoDay - 1) . ' days');
    }

    private function dowToKey(int $isoDay): ?string
    {
        return match ($isoDay) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => null,
        };
    }

    private function isOpenOnDate(Establishment $establishment, \DateTime $date): bool
    {
        $dayKey = $this->dowToKey((int) $date->format('N'));
        if (!$dayKey) return false;

        foreach ($establishment->getOpeningHours() as $oh) {
            if ($oh->getDayOfWeek() === $dayKey) {
                return true;
            }
        }
        return false;
    }

    private function generateAvailableSlots(
        EntityManagerInterface $em,
        Establishment $establishment,
        Service $service,
        \DateTime $date
    ): array {
        $dayKey = $this->dowToKey((int) $date->format('N'));
        if (!$dayKey) return [];

        $opening = null;
        foreach ($establishment->getOpeningHours() as $oh) {
            if ($oh->getDayOfWeek() === $dayKey) { $opening = $oh; break; }
        }
        if (!$opening) return [];

        $openTime  = $opening->getOpenTime();
        $closeTime = $opening->getCloseTime();
        if (!$openTime || !$closeTime) return [];

        $open  = (clone $date)->setTime((int) $openTime->format('H'), (int) $openTime->format('i'), 0);
        $close = (clone $date)->setTime((int) $closeTime->format('H'), (int) $closeTime->format('i'), 0);

        $duration = (int) $service->getDuration();
        if ($duration <= 0) return [];

        $buffer = (int) ($service->getBufferTime() ?? 0);
        $stepMinutes = $duration;

        $professional = $establishment->getOwner();
        if (!$professional instanceof User) return [];

        $slots = [];
        $cursor = clone $open;

        while ($cursor < $close) {
            $start = clone $cursor;
            $end   = (clone $start)->modify("+{$duration} minutes");

            if ($buffer > 0) {
                $end = (clone $end)->modify("+{$buffer} minutes");
            }

            if ($end > $close) break;

            if (!$this->hasOverlap($em, $professional, $date, $start, $end)) {
                $t = $start->format('H:i');
                $slots[] = ['time' => $t, 'label' => $t];
            }

            $cursor->modify("+{$stepMinutes} minutes");
        }

        return $slots;
    }

    private function hasOverlap(
        EntityManagerInterface $em,
        User $professional,
        \DateTime $date,
        \DateTime $start,
        \DateTime $end
    ): bool {
        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(a.id)')
            ->from(Appointment::class, 'a')
            ->andWhere('a.professional = :pro')
            ->andWhere('a.date = :date')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.startTime < :end')
            ->andWhere('a.endTime > :start')
            ->setParameter('pro', $professional)
            ->setParameter('date', $date)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
