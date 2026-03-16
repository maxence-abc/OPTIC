<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\UserRepository;
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
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $serviceId = $request->query->getInt('service');
        $dateStr   = (string) $request->query->get('date'); // YYYY-MM-DD
        $timeStr   = (string) $request->query->get('time'); // HH:mm
        $weekStr   = (string) $request->query->get('week'); // YYYY-MM-DD
        $professionalId = $request->query->getInt('professional');

        // ✅ Image hero depuis /public/uploads/establishments/{id}/...
        $heroSrc = $this->findHeroImageForEstablishment((int) $establishment->getId());
        $professionalCandidates = $userRepository->findBookableCandidatesByEstablishment($establishment);
        $selectedProfessional = $this->resolveSelectedProfessional($professionalId, $professionalCandidates);

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
            if ($this->isOpenOnDate($establishment, $d) && !$this->isPastDay($d)) {
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
        if (!$selectedDate || $this->isPastDay($selectedDate) || !$this->isOpenOnDate($establishment, $selectedDate)) {
            $selectedDate = !empty($openWeekDays) ? (clone $openWeekDays[0])->setTime(0, 0, 0) : null;
        }

        // Créneaux
        $slots = [];
        if ($selectedService && $selectedDate) {
            $slots = $this->generateAvailableSlots(
                $em,
                $establishment,
                $selectedService,
                $selectedDate,
                $professionalCandidates,
                $selectedProfessional
            );
        }

        return $this->render('establishment_page/show.html.twig', [
            'establishment'     => $establishment,
            'heroSrc'           => $heroSrc,

            'selectedService'   => $selectedService,
            'selectedServiceId' => $selectedService?->getId(),
            'weekStart'         => $weekStart,
            'openWeekDays'      => $openWeekDays,
            'professionalCandidates' => $professionalCandidates,
            'selectedProfessional' => $selectedProfessional,
            'selectedProfessionalId' => $selectedProfessional?->getId(),
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
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if (!$this->isCsrfTokenValid('book_appointment', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        $serviceId = (int) $request->request->get('service');
        $dateStr   = (string) $request->request->get('date');
        $timeStr   = (string) $request->request->get('time');
        $professionalId = $request->request->getInt('professional');
        $professionalCandidates = $userRepository->findBookableCandidatesByEstablishment($establishment);
        $selectedProfessional = $this->resolveSelectedProfessional($professionalId, $professionalCandidates);

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

        if ($this->isPastDay($date) || !$this->isOpenOnDate($establishment, $date)) {
            $this->addFlash('error', 'Cette date n’est plus réservable.');
            return $this->redirectToRoute('front_establishment_show', [
                'id' => $establishment->getId(),
                'service' => $service->getId(),
            ]);
        }

        if ($professionalId > 0 && !$selectedProfessional instanceof User) {
            $this->addFlash('error', 'Le professionnel choisi est invalide.');
            return $this->redirectToRoute('front_establishment_show', $this->buildBookingRedirectParams(
                $establishment,
                $service,
                $date
            ));
        }

        if (!$professionalCandidates) {
            $this->addFlash('error', 'Aucun professionnel associé.');
            return $this->redirectToRoute('front_establishment_show', ['id' => $establishment->getId()]);
        }

        if (!str_contains($timeStr, ':')) {
            $this->addFlash('error', 'Heure invalide.');
            return $this->redirectToRoute('front_establishment_show', $this->buildBookingRedirectParams(
                $establishment,
                $service,
                $date,
                $selectedProfessional
            ));
        }

        // Re-check slots
        $slots = $this->generateAvailableSlots(
            $em,
            $establishment,
            $service,
            $date,
            $professionalCandidates,
            $selectedProfessional
        );
        $slotOk = false;
        foreach ($slots as $s) {
            if ($s['time'] === $timeStr) { $slotOk = true; break; }
        }
        if (!$slotOk) {
            $this->addFlash('error', 'Ce créneau n’est plus disponible.');
            return $this->redirectToRoute('front_establishment_show', $this->buildBookingRedirectParams(
                $establishment,
                $service,
                $date,
                $selectedProfessional
            ));
        }

        [$h, $m] = array_map('intval', explode(':', $timeStr));
        $start = (clone $date)->setTime($h, $m, 0);

        $duration = (int) $service->getDuration();
        $buffer   = (int) ($service->getBufferTime() ?? 0);

        $end = (clone $start)->modify("+{$duration} minutes");
        if ($buffer > 0) {
            $end = (clone $end)->modify("+{$buffer} minutes");
        }

        if ($this->isPastSlot($start)) {
            $this->addFlash('error', 'Impossible de réserver un créneau déjà passé.');

            return $this->redirectToRoute('front_establishment_show', $this->buildBookingRedirectParams(
                $establishment,
                $service,
                $date,
                $selectedProfessional
            ));
        }

        $assignableProfessionals = $selectedProfessional instanceof User ? [$selectedProfessional] : $professionalCandidates;
        $assignedProfessional = null;

        foreach ($assignableProfessionals as $candidateProfessional) {
            if (!$this->hasOverlap($em, $candidateProfessional, $date, $start, $end)) {
                $assignedProfessional = $candidateProfessional;
                break;
            }
        }

        if (!$assignedProfessional instanceof User) {
            $this->addFlash(
                'error',
                $selectedProfessional instanceof User
                    ? 'Le professionnel choisi n’est plus disponible sur ce créneau.'
                    : 'Aucun professionnel n’est disponible sur ce créneau.'
            );

            return $this->redirectToRoute('front_establishment_show', $this->buildBookingRedirectParams(
                $establishment,
                $service,
                $date,
                $selectedProfessional
            ));
        }

        $appointment = new Appointment();
        $appointment->setClient($this->getUser());
        $appointment->setProfessional($assignedProfessional);
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
            'professional' => $selectedProfessional?->getId(),
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
        \DateTime $date,
        array $professionalCandidates,
        ?User $selectedProfessional = null
    ): array {
        if ($this->isPastDay($date)) {
            return [];
        }

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

        $professionals = $selectedProfessional instanceof User ? [$selectedProfessional] : $professionalCandidates;
        if (!$professionals) return [];

        $slots = [];
        $cursor = clone $open;

        while ($cursor < $close) {
            $start = clone $cursor;
            $end   = (clone $start)->modify("+{$duration} minutes");

            if ($buffer > 0) {
                $end = (clone $end)->modify("+{$buffer} minutes");
            }

            if ($end > $close) break;

            if ($this->isPastSlot($start)) {
                $cursor->modify("+{$stepMinutes} minutes");
                continue;
            }

            $hasAvailableProfessional = false;
            foreach ($professionals as $professional) {
                if (!$this->hasOverlap($em, $professional, $date, $start, $end)) {
                    $hasAvailableProfessional = true;
                    break;
                }
            }

            if ($hasAvailableProfessional) {
                $t = $start->format('H:i');
                $slots[] = ['time' => $t, 'label' => $t];
            }

            $cursor->modify("+{$stepMinutes} minutes");
        }

        return $slots;
    }

    /**
     * @param User[] $professionalCandidates
     */
    private function resolveSelectedProfessional(int $professionalId, array $professionalCandidates): ?User
    {
        if ($professionalId <= 0) {
            return null;
        }

        foreach ($professionalCandidates as $professionalCandidate) {
            if ($professionalCandidate->getId() === $professionalId) {
                return $professionalCandidate;
            }
        }

        return null;
    }

    private function buildBookingRedirectParams(
        Establishment $establishment,
        ?Service $service = null,
        ?\DateTime $date = null,
        ?User $professional = null
    ): array {
        $params = [
            'id' => $establishment->getId(),
        ];

        if ($service instanceof Service && $service->getId()) {
            $params['service'] = $service->getId();
        }

        if ($date instanceof \DateTime) {
            $params['date'] = $date->format('Y-m-d');
            $params['week'] = $this->weekStart($date->format('Y-m-d'))->format('Y-m-d');
        }

        if ($professional?->getId()) {
            $params['professional'] = $professional->getId();
        }

        return $params;
    }

    private function isPastDay(\DateTimeInterface $date): bool
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0) < new \DateTimeImmutable('today');
    }

    private function isPastSlot(\DateTimeInterface $start): bool
    {
        return \DateTimeImmutable::createFromInterface($start) <= new \DateTimeImmutable();
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
