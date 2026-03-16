<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\UserRepository;
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
        if (in_array($redirectRoute, ['app_pro_dashboard', 'app_pro_reservations', 'app_pro_profile'], true)) {
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
}
