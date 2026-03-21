<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\AppointmentRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\OpeningHoursService;
use App\Service\ReviewEligibilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/establishments')]
final class EstablishmentPageController extends AbstractController
{
    public function __construct(
        private readonly OpeningHoursService $openingHoursService
    ) {
    }

    #[Route('/{id}', name: 'front_establishment_show', methods: ['GET'])]
    public function show(
        Establishment $establishment,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService
    ): Response {
        $query = $request->query->all();
        $serviceId = $this->parsePositiveInt($query['service'] ?? null);
        $dateStr   = (string) $request->query->get('date'); // YYYY-MM-DD
        $timeStr   = (string) $request->query->get('time'); // HH:mm
        $weekStr   = (string) $request->query->get('week'); // YYYY-MM-DD
        $professionalId = $this->parsePositiveInt($query['professional'] ?? null);

        $heroSrc = $establishment->getPrimaryImage()?->getPublicPath() ?? '/images/placeholder-establishment.jpg';
        $heroImages = [];

        foreach ($establishment->getEstablishmentImages() as $image) {
            $publicPath = $image->getPublicPath();
            if ($publicPath !== null && $publicPath !== '') {
                $heroImages[] = $publicPath;
            }
        }

        if ($heroImages === []) {
            $heroImages[] = $heroSrc;
        }

        $reviewStats = $reviewRepository->getSummaryForEstablishment($establishment);
        $establishmentReviews = $reviewRepository->findPublicByEstablishment($establishment, 8);
        $reviewBaseParams = $request->query->all();
        unset($reviewBaseParams['review']);
        $reviewBaseParams['id'] = $establishment->getId();
        $reviewBaseParams['_fragment'] = 'reviews';
        $requestedReviewAppointmentId = $this->parsePositiveInt($query['review'] ?? null);
        $reviewableAppointment = null;
        $reviewModalAppointment = null;
        $reviewForm = null;

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
        $openWeekDays = $this->buildReservableWeekDays($establishment, $weekStart);

        if ($openWeekDays === []) {
            $nextReservableWeekStart = $this->findNextReservableWeekStart($establishment, $weekStart);
            if ($nextReservableWeekStart instanceof \DateTime) {
                $weekStart = $nextReservableWeekStart;
                $openWeekDays = $this->buildReservableWeekDays($establishment, $weekStart);
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

        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            foreach ($appointmentRepository->findPastForClient($currentUser, 100) as $appointment) {
                if (!$reviewEligibilityService->canReviewEstablishmentAppointment($currentUser, $establishment, $appointment)) {
                    continue;
                }

                $reviewableAppointment ??= $appointment;

                if ($requestedReviewAppointmentId > 0 && $appointment->getId() === $requestedReviewAppointmentId) {
                    $reviewModalAppointment = $appointment;
                    break;
                }
            }
        }

        if ($currentUser instanceof User && $reviewModalAppointment instanceof Appointment) {
            $review = (new Review())
                ->setAppointment($reviewModalAppointment)
                ->setClient($currentUser)
                ->setEstablishment($establishment);

            $reviewForm = $this->createForm(ReviewType::class, $review)->createView();
        }

        return $this->render('establishment_page/show.html.twig', [
            'establishment'     => $establishment,
            'heroSrc'           => $heroSrc,
            'heroImages'        => $heroImages,
            'establishmentReviews' => $establishmentReviews,
            'reviewStats'       => $reviewStats,
            'reviewBaseParams'  => $reviewBaseParams,
            'reviewableAppointment' => $reviewableAppointment,
            'reviewModalAppointment' => $reviewModalAppointment,
            'reviewForm'        => $reviewForm,

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

        $requestData = $request->request->all();
        $serviceId = $this->parsePositiveInt($requestData['service'] ?? null);
        $dateStr   = (string) $request->request->get('date');
        $timeStr   = (string) $request->request->get('time');
        $professionalId = $this->parsePositiveInt($requestData['professional'] ?? null);
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

    private function weekStart(string $dateStr): \DateTime
    {
        try { $d = new \DateTime($dateStr); }
        catch (\Throwable $e) { $d = new \DateTime('today'); }

        $d->setTime(0, 0, 0);
        $isoDay = (int) $d->format('N');
        return $d->modify('-' . ($isoDay - 1) . ' days');
    }

    /**
     * @return \DateTime[]
     */
    private function buildReservableWeekDays(Establishment $establishment, \DateTime $weekStart): array
    {
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $weekStart)->modify("+{$i} days");

            if ($this->isOpenOnDate($establishment, $date) && !$this->isPastDay($date)) {
                $days[] = $date;
            }
        }

        return $days;
    }

    private function findNextReservableWeekStart(Establishment $establishment, \DateTime $requestedWeekStart, int $maxWeeks = 12): ?\DateTime
    {
        $todayWeekStart = $this->weekStart('today');
        $searchStart = $requestedWeekStart < $todayWeekStart ? $todayWeekStart : clone $requestedWeekStart;

        for ($weekOffset = 0; $weekOffset < $maxWeeks; ++$weekOffset) {
            $candidateWeekStart = (clone $searchStart)->modify('+' . (7 * $weekOffset) . ' days');

            if ($this->buildReservableWeekDays($establishment, $candidateWeekStart) !== []) {
                return $candidateWeekStart;
            }
        }

        return null;
    }

    private function isOpenOnDate(Establishment $establishment, \DateTime $date): bool
    {
        return $this->openingHoursService->isOpenOnDate($establishment, $date);
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

        $openingHours = $this->openingHoursService->getIntervalsForDate($establishment, $date);
        if ($openingHours === []) return [];

        $duration = (int) $service->getDuration();
        if ($duration <= 0) return [];

        $buffer = (int) ($service->getBufferTime() ?? 0);
        $stepMinutes = $duration;

        $professionals = $selectedProfessional instanceof User ? [$selectedProfessional] : $professionalCandidates;
        if (!$professionals) return [];

        $slots = [];

        foreach ($openingHours as $openingHour) {
            $openTime = $openingHour->getOpenTime();
            $closeTime = $openingHour->getCloseTime();
            if (!$openTime || !$closeTime) {
                continue;
            }

            $open = (clone $date)->setTime((int) $openTime->format('H'), (int) $openTime->format('i'), 0);
            $close = (clone $date)->setTime((int) $closeTime->format('H'), (int) $closeTime->format('i'), 0);
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
                    $slots[$t] = ['time' => $t, 'label' => $t];
                }

                $cursor->modify("+{$stepMinutes} minutes");
            }
        }

        return array_values($slots);
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

    private function parsePositiveInt(mixed $value): int
    {
        if (\is_int($value)) {
            return $value > 0 ? $value : 0;
        }

        if (!\is_string($value)) {
            return 0;
        }

        $value = trim($value);

        if ($value === '' || !ctype_digit($value)) {
            return 0;
        }

        return (int) $value;
    }
}
