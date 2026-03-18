<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\EmployeeScheduleEvent;
use App\Entity\EmployeeWeeklySchedule;
use App\Entity\Establishment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\EmployeeScheduleEventRepository;
use App\Repository\EmployeeWeeklyScheduleRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\UserRepository;
use App\Service\EmployeeWeeklyScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pro')]
#[IsGranted('ROLE_PRO')]
final class ProDashboardController extends AbstractController
{
    private const SESSION_ACTIVE_ESTABLISHMENT = 'pro_active_establishment_id';

    public function __construct(
        private readonly EstablishmentRepository $establishmentRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    #[Route('/entreprise', name: 'app_pro_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository
    ): Response
    {
        $establishment = $this->resolveCurrentEstablishment($request);
        if (!$establishment instanceof Establishment) {
            $this->addFlash('warning', 'Aucun établissement n’est associé à votre compte.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('pro_dashboard/dashboard.html.twig', $this->buildDashboardContext(
            $appointmentRepository,
            $userRepository,
            $establishment
        ));
    }

    #[Route('/reservations', name: 'app_pro_reservations', methods: ['GET'])]
    public function reservations(
        Request $request,
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository
    ): Response {
        $establishment = $this->resolveCurrentEstablishment($request);
        if (!$establishment instanceof Establishment) {
            $this->addFlash('warning', 'Aucun établissement n’est associé à votre compte.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('pro_dashboard/reservations.html.twig', $this->buildDashboardContext(
            $appointmentRepository,
            $userRepository,
            $establishment
        ));
    }

    #[Route('/profile', name: 'app_pro_profile', methods: ['GET'])]
    public function profile(
        Request $request,
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository
    ): Response {
        $establishment = $this->resolveCurrentEstablishment($request);
        if (!$establishment instanceof Establishment) {
            $this->addFlash('warning', 'Aucun établissement n’est associé à votre compte.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('pro_dashboard/profile.html.twig', $this->buildDashboardContext(
            $appointmentRepository,
            $userRepository,
            $establishment
        ));
    }

    #[Route('/calendar', name: 'app_pro_calendar', methods: ['GET'])]
    public function calendar(
        Request $request,
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository,
        EmployeeScheduleEventRepository $scheduleEventRepository,
        EmployeeWeeklyScheduleRepository $weeklyScheduleRepository
    ): Response {
        $establishment = $this->resolveCurrentEstablishment($request);
        if (!$establishment instanceof Establishment) {
            $this->addFlash('warning', 'Aucun établissement n’est associé à votre compte.');

            return $this->redirectToRoute('app_home');
        }

        $selectedView = (string) $request->query->get('view', 'planning');
        if (!in_array($selectedView, ['planning', 'calendar'], true)) {
            $selectedView = 'planning';
        }

        $user = $this->getCurrentUser();
        $anchorDate = $this->resolveCalendarAnchorDate($request);
        $today = new \DateTimeImmutable('today');
        $weekStart = $this->getWeekStart($anchorDate);
        $weekEnd = $weekStart->modify('+6 days');
        $weeklyScheduleService = new EmployeeWeeklyScheduleService();

        $weeklySchedules = $weeklyScheduleRepository->findByEmployee($establishment, $user);
        $weeklyScheduleIndex = $this->indexWeeklySchedules($weeklySchedules, $weeklyScheduleService);
        $todayScheduleEvents = $scheduleEventRepository->findByEmployeeBetweenDates($user, $today, $today);
        $weeklyScheduleEvents = $scheduleEventRepository->findByEmployeeBetweenDates($user, $weekStart, $weekEnd);
        $todayAppointments = array_values(array_filter(
            $appointmentRepository->findByEstablishmentBetweenDates($establishment, $today, $today->modify('+1 day'), $user),
            static fn (Appointment $appointment): bool => $appointment->getStatus() !== 'cancelled'
        ));
        $weeklyAppointments = array_values(array_filter(
            $appointmentRepository->findByEstablishmentBetweenDates($establishment, $weekStart, $weekEnd, $user),
            static fn (Appointment $appointment): bool => $appointment->getStatus() !== 'cancelled'
        ));

        return $this->render('pro_dashboard/calendar.html.twig', array_merge(
            $this->buildDashboardContext($appointmentRepository, $userRepository, $establishment),
            [
                'selectedView' => $selectedView,
                'anchorDate' => $anchorDate,
                'planningWeekStart' => $weekStart,
                'planningWeekEnd' => $weekEnd,
                'calendarWeekDays' => $this->buildPeriodDays($weekStart, 7),
                'todayScheduleEvents' => array_values(array_filter($todayScheduleEvents, static fn (EmployeeScheduleEvent $event): bool => $event->occursOn($today))),
                'todayDefaultSchedules' => $weeklyScheduleIndex[(int) $today->format('N')] ?? [],
                'todayDefaultSchedulesDisplay' => $weeklyScheduleService->formatDisplayRanges($weeklyScheduleIndex[(int) $today->format('N')] ?? []),
                'todayAppointmentsForProfessional' => $todayAppointments,
                'weeklyPlanningDays' => $this->buildProfessionalPlanningDays($weekStart, $weeklySchedules, $weeklyScheduleEvents, $weeklyAppointments, $weeklyScheduleService),
            ]
        ));
    }

    #[Route('/appointment/{id}/accept', name: 'app_pro_appointment_accept', methods: ['POST'])]
    public function accept(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $this->assertAppointmentCanBeManaged($appointment, $request);

        if (!$this->isCsrfTokenValid('pro_accept_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToProSpace($request);
        }

        if (!$this->isManageableAppointment($appointment)) {
            $this->addFlash('error', 'Seuls les rendez-vous du jour ou à venir peuvent être acceptés.');

            return $this->redirectToProSpace($request);
        }

        if ($appointment->getStatus() === 'cancelled') {
            $this->addFlash('error', 'Ce rendez-vous a déjà été annulé.');

            return $this->redirectToProSpace($request);
        }

        if ($appointment->getStatus() === 'confirmed') {
            $this->addFlash('info', 'Ce rendez-vous est déjà accepté.');

            return $this->redirectToProSpace($request);
        }

        $appointment->setStatus('confirmed');
        $entityManager->flush();

        $this->addFlash('success', 'Le rendez-vous a été accepté.');

        return $this->redirectToProSpace($request);
    }

    #[Route('/appointment/{id}/cancel', name: 'app_pro_appointment_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $this->assertAppointmentCanBeManaged($appointment, $request);

        if (!$this->isCsrfTokenValid('pro_cancel_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToProSpace($request);
        }

        if (!$this->isManageableAppointment($appointment)) {
            $this->addFlash('error', 'Seuls les rendez-vous du jour ou à venir peuvent être annulés.');

            return $this->redirectToProSpace($request);
        }

        if ($appointment->getStatus() === 'cancelled') {
            $this->addFlash('info', 'Ce rendez-vous est déjà annulé.');

            return $this->redirectToProSpace($request);
        }

        $appointment->setStatus('cancelled');
        $entityManager->flush();

        $this->addFlash('success', 'Le rendez-vous a été annulé.');

        return $this->redirectToProSpace($request);
    }

    #[Route('/appointment/{id}/transfer', name: 'app_pro_appointment_transfer', methods: ['POST'])]
    public function transfer(
        Request $request,
        Appointment $appointment,
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $actor = $this->getCurrentUser();
        $establishment = $this->assertAppointmentCanBeManaged($appointment, $request);

        if (!$this->isCsrfTokenValid('pro_transfer_'.$appointment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToProSpace($request);
        }

        if (!$this->isManageableAppointment($appointment)) {
            $this->addFlash('error', 'Seuls les rendez-vous du jour ou à venir peuvent être transférés.');

            return $this->redirectToProSpace($request);
        }

        if ($appointment->getStatus() === 'cancelled') {
            $this->addFlash('error', 'Un rendez-vous annulé ne peut pas être transféré.');

            return $this->redirectToProSpace($request);
        }

        $professionalId = $request->request->getInt('professional_id');
        if ($professionalId <= 0) {
            $this->addFlash('error', 'Sélectionnez un professionnel pour le transfert.');

            return $this->redirectToProSpace($request);
        }

        $targetProfessional = $userRepository->find($professionalId);
        if (!$targetProfessional instanceof User) {
            $this->addFlash('error', 'Professionnel introuvable.');

            return $this->redirectToProSpace($request);
        }

        if ($targetProfessional->getEstablishment()?->getId() !== $establishment->getId() || !$this->isProfessional($targetProfessional)) {
            $this->addFlash('error', 'Le professionnel choisi ne fait pas partie de votre établissement.');

            return $this->redirectToProSpace($request);
        }

        if ($appointment->getProfessional()?->getId() === $targetProfessional->getId()) {
            $this->addFlash('info', 'Ce rendez-vous est déjà assigné à ce professionnel.');

            return $this->redirectToProSpace($request);
        }

        $date = $appointment->getDate();
        $startTime = $appointment->getStartTime();
        $endTime = $appointment->getEndTime();

        if (!$date || !$startTime || !$endTime) {
            $this->addFlash('error', 'Ce rendez-vous est invalide et ne peut pas être transféré.');

            return $this->redirectToProSpace($request);
        }

        if ($appointmentRepository->hasOverlapForProfessional($targetProfessional->getId(), $date, $startTime, $endTime)) {
            $this->addFlash('error', 'Le professionnel choisi a déjà un rendez-vous sur ce créneau.');

            return $this->redirectToProSpace($request);
        }

        $appointment->setProfessional($targetProfessional);
        $appointment->setTransferredBy($actor);
        $appointment->setTransferredAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Le rendez-vous a été transféré.');

        return $this->redirectToProSpace($request);
    }

    private function getCurrentUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non connecté.');
        }

        return $user;
    }

    private function assertAppointmentCanBeManaged(Appointment $appointment, ?Request $request = null): Establishment
    {
        $user = $this->getCurrentUser();
        $establishment = $this->resolveCurrentEstablishment($request);
        $appointmentEstablishment = $appointment->getService()?->getEstablishment();

        if (!$establishment || !$appointmentEstablishment || $appointmentEstablishment->getId() !== $establishment->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas gérer ce rendez-vous.');
        }

        if ($this->shouldScopeAppointmentsToCurrentProfessional() && $appointment->getProfessional()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez gérer que vos rendez-vous assignés.');
        }

        return $establishment;
    }

    private function isManageableAppointment(Appointment $appointment): bool
    {
        $date = $appointment->getDate();
        $endTime = $appointment->getEndTime();

        if (!$date || !$endTime) {
            return false;
        }

        $appointmentEndAt = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            sprintf('%s %s', $date->format('Y-m-d'), $endTime->format('H:i:s'))
        );

        if (!$appointmentEndAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $appointmentEndAt > new \DateTimeImmutable();
    }

    private function isProfessional(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array('ROLE_PRO', $roles, true)
            || in_array('ROLE_ADMIN_PRO', $roles, true)
            || in_array('ROLE_ADMIN', $roles, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardContext(
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository,
        Establishment $establishment
    ): array {
        $user = $this->getCurrentUser();
        $professionalScope = $this->getProfessionalScope();

        return [
            'establishment' => $establishment,
            'professionalUser' => $user,
            'services' => $establishment->getServices(),
            'employees' => $establishment->getUsers(),
            'openingHours' => $establishment->getOpeningHours(),
            'todayAppointments' => $appointmentRepository->findByEstablishmentForToday($establishment),
            'upcomingAppointments' => $appointmentRepository->findUpcomingByEstablishment($establishment, $professionalScope),
            'manageableAppointments' => $appointmentRepository->findManageableByEstablishment($establishment, $professionalScope),
            'totalAppointments' => $appointmentRepository->countByEstablishment($establishment),
            'monthAppointments' => $appointmentRepository->countByEstablishmentForCurrentMonth($establishment),
            'transferProfessionals' => $userRepository->findProfessionalsByEstablishment($establishment),
        ];
    }

    private function getProfessionalScope(): ?User
    {
        if ($this->shouldScopeAppointmentsToCurrentProfessional()) {
            return $this->getCurrentUser();
        }

        return null;
    }

    private function shouldScopeAppointmentsToCurrentProfessional(): bool
    {
        return !$this->isGranted('ROLE_ADMIN_PRO') && !$this->isGranted('ROLE_ADMIN');
    }

    private function redirectToProSpace(Request $request): RedirectResponse
    {
        $redirectRoute = (string) $request->request->get('_redirect_route', '');
        if (in_array($redirectRoute, ['app_pro_dashboard', 'app_pro_reservations', 'app_pro_profile', 'app_pro_calendar'], true)) {
            return $this->redirectToRoute($redirectRoute);
        }

        $referer = (string) $request->headers->get('referer', '');

        if ($referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/pro/')) {
                return new RedirectResponse($referer);
            }
        }

        return $this->redirectToRoute('app_pro_dashboard');
    }

    private function resolveCurrentEstablishment(?Request $request = null): ?Establishment
    {
        $request ??= $this->requestStack->getCurrentRequest();
        $user = $this->getCurrentUser();

        if ($request instanceof Request) {
            $queryEstablishmentId = $request->query->getInt('establishment');
            if ($queryEstablishmentId > 0) {
                $queryEstablishment = $this->establishmentRepository->find($queryEstablishmentId);
                if ($queryEstablishment instanceof Establishment && $this->canAccessEstablishment($user, $queryEstablishment)) {
                    $request->getSession()?->set(self::SESSION_ACTIVE_ESTABLISHMENT, $queryEstablishment->getId());

                    return $queryEstablishment;
                }
            }

            $sessionEstablishmentId = (int) ($request->getSession()?->get(self::SESSION_ACTIVE_ESTABLISHMENT) ?? 0);
            if ($sessionEstablishmentId > 0) {
                $sessionEstablishment = $this->establishmentRepository->find($sessionEstablishmentId);
                if ($sessionEstablishment instanceof Establishment && $this->canAccessEstablishment($user, $sessionEstablishment)) {
                    return $sessionEstablishment;
                }
            }
        }

        $defaultEstablishment = $this->resolveDefaultEstablishment($user);
        if ($defaultEstablishment instanceof Establishment && $request instanceof Request) {
            $request->getSession()?->set(self::SESSION_ACTIVE_ESTABLISHMENT, $defaultEstablishment->getId());
        }

        return $defaultEstablishment;
    }

    private function resolveDefaultEstablishment(User $user): ?Establishment
    {
        if ($user->getEstablishment() instanceof Establishment) {
            return $user->getEstablishment();
        }

        if ($this->isGranted('ROLE_ADMIN_PRO') || $this->isGranted('ROLE_ADMIN')) {
            return $this->establishmentRepository->findOneBy(['owner' => $user], ['id' => 'ASC']);
        }

        return null;
    }

    private function canAccessEstablishment(User $user, Establishment $establishment): bool
    {
        if ($user->getEstablishment()?->getId() === $establishment->getId()) {
            return true;
        }

        if (($this->isGranted('ROLE_ADMIN_PRO') || $this->isGranted('ROLE_ADMIN'))
            && $establishment->getOwner()?->getId() === $user->getId()) {
            return true;
        }

        return false;
    }

    private function getWeekStart(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('monday this week')->setTime(0, 0);
    }

    private function resolveCalendarAnchorDate(Request $request): \DateTimeImmutable
    {
        $date = trim((string) $request->query->get('date', ''));

        if ($date !== '') {
            try {
                return new \DateTimeImmutable($date);
            } catch (\Throwable) {
            }
        }

        return new \DateTimeImmutable('today');
    }

    /**
     * @return array<int, array{date: \DateTimeImmutable, label: string, short: string}>
     */
    private function buildPeriodDays(\DateTimeImmutable $start, int $length): array
    {
        $labels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $days = [];

        for ($index = 0; $index < $length; ++$index) {
            $date = $start->modify(sprintf('+%d days', $index));
            $days[] = [
                'date' => $date,
                'label' => $labels[$index] ?? $date->format('D'),
                'short' => $date->format('d/m'),
            ];
        }

        return $days;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @param EmployeeScheduleEvent[] $scheduleEvents
     * @param Appointment[] $appointments
     * @return array<int, array<string, mixed>>
     */
    private function buildProfessionalPlanningDays(\DateTimeImmutable $weekStart, array $weeklySchedules, array $scheduleEvents, array $appointments, EmployeeWeeklyScheduleService $weeklyScheduleService): array
    {
        $weeklyScheduleIndex = $this->indexWeeklySchedules($weeklySchedules, $weeklyScheduleService);
        $appointmentsByDay = [];
        foreach ($appointments as $appointment) {
            $date = $appointment->getDate();
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            $appointmentsByDay[$date->format('Y-m-d')][] = $appointment;
        }

        $days = [];
        for ($index = 0; $index < 7; ++$index) {
            $date = $weekStart->modify(sprintf('+%d days', $index));
            $key = $date->format('Y-m-d');
            $dayEvents = array_values(array_filter($scheduleEvents, static fn (EmployeeScheduleEvent $event): bool => $event->occursOn($date)));
            $dayAppointments = $appointmentsByDay[$key] ?? [];
            $defaultSchedules = $weeklyScheduleIndex[(int) $date->format('N')] ?? [];

            $days[] = [
                'date' => $date,
                'events' => $dayEvents,
                'defaultSchedules' => $defaultSchedules,
                'appointments' => $dayAppointments,
                'summary' => $this->buildProfessionalPlanningSummary($defaultSchedules, $dayEvents, $dayAppointments, $weeklyScheduleService),
            ];
        }

        return $days;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return array<int, EmployeeWeeklySchedule[]>
     */
    private function indexWeeklySchedules(array $weeklySchedules, EmployeeWeeklyScheduleService $weeklyScheduleService): array
    {
        return $weeklyScheduleService->indexByDay($weeklySchedules);
    }

    /**
     * @param EmployeeWeeklySchedule[] $defaultSchedules
     * @param EmployeeScheduleEvent[] $events
     * @param Appointment[] $appointments
     * @return array{label: string, class: string, sublabel: string}
     */
    private function buildProfessionalPlanningSummary(array $defaultSchedules, array $events, array $appointments, EmployeeWeeklyScheduleService $weeklyScheduleService): array
    {
        if ($events !== []) {
            usort($events, static fn (EmployeeScheduleEvent $left, EmployeeScheduleEvent $right): int => self::getPlanningTypePriority($left->getType()) <=> self::getPlanningTypePriority($right->getType()));
            $event = $events[0];

            return [
                'label' => ($event->getType() ?? EmployeeScheduleEvent::TYPE_WORK) === EmployeeScheduleEvent::TYPE_WORK ? 'Actif' : $event->getTypeLabel(),
                'class' => 'is-'.($event->getType() ?? EmployeeScheduleEvent::TYPE_WORK),
                'sublabel' => $event->isAllDay()
                    ? $event->getDisplayTitle()
                    : trim(sprintf('%s - %s', $event->getStartTime()?->format('H:i') ?? '', $event->getEndTime()?->format('H:i') ?? '')),
            ];
        }

        if ($weeklyScheduleService->getConfiguredIntervals($defaultSchedules) !== []) {
            return [
                'label' => 'Actif',
                'class' => 'is-work',
                'sublabel' => $weeklyScheduleService->formatDisplayRanges($defaultSchedules),
            ];
        }

        if ($appointments !== []) {
            return [
                'label' => 'Réservations',
                'class' => 'is-appointments',
                'sublabel' => sprintf('%d réservation%s', count($appointments), count($appointments) > 1 ? 's' : ''),
            ];
        }

        return [
            'label' => 'Repos',
            'class' => 'is-rest',
            'sublabel' => 'Jour non travaillé',
        ];
    }

    private static function getPlanningTypePriority(?string $type): int
    {
        return match ($type) {
            EmployeeScheduleEvent::TYPE_LEAVE => 0,
            EmployeeScheduleEvent::TYPE_REST => 1,
            EmployeeScheduleEvent::TYPE_TRAINING => 2,
            EmployeeScheduleEvent::TYPE_WORK => 3,
            default => 4,
        };
    }
}
