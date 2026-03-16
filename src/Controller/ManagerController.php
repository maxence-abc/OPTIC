<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Entity\User;
use App\Form\ManagerEmployeeType;
use App\Form\OpeningHourType;
use App\Form\ServiceType;
use App\Repository\AppointmentRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\OpeningHourRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Security\Voter\EstablishmentVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/manager')]
final class ManagerController extends AbstractController
{
    private const SESSION_ACTIVE_ESTABLISHMENT = 'manager_active_establishment_id';

    #[Route('', name: 'app_manager_home', methods: ['GET'])]
    public function home(EstablishmentRepository $establishmentRepository, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_PRO');

        $user = $this->getUser();
        $owned = $establishmentRepository->findBy(['owner' => $user], ['id' => 'DESC']);

        if (!$owned) {
            throw $this->createNotFoundException("Aucun établissement assigné à ce compte gérant.");
        }

        $activeId = $session->get(self::SESSION_ACTIVE_ESTABLISHMENT);
        if ($activeId) {
            $active = $establishmentRepository->find($activeId);
            if ($active && $this->isGranted(EstablishmentVoter::MANAGE, $active)) {
                return $this->redirectToRoute('manager_dashboard', ['id' => $active->getId()]);
            }
            $session->remove(self::SESSION_ACTIVE_ESTABLISHMENT);
        }

        if (count($owned) === 1) {
            $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $owned[0]->getId());
            return $this->redirectToRoute('manager_dashboard', ['id' => $owned[0]->getId()]);
        }

        return $this->render('manager/select_establishment.html.twig', [
            'establishments' => $this->buildEstablishmentCards($owned),
        ]);
    }

    #[Route('/switch/{id}', name: 'manager_switch_establishment', methods: ['POST'])]
    public function switchEstablishment(Establishment $establishment, SessionInterface $session, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $currentRoute = (string) $request->request->get('_current_route', 'manager_dashboard');

        return match ($currentRoute) {
            'manager_services',
            'manager_service_edit' => $this->redirectToRoute('manager_services', [
                'id' => $establishment->getId(),
            ]),

            'manager_employees',
            'manager_employee_new',
            'manager_employee_edit' => $this->redirectToRoute('manager_employees', [
                'id' => $establishment->getId(),
            ]),

            'manager_opening_hours',
            'manager_opening_hour_new',
            'manager_opening_hour_edit' => $this->redirectToRoute('manager_opening_hours', [
                'id' => $establishment->getId(),
            ]),

            'manager_settings' => $this->redirectToRoute('manager_settings', [
                'id' => $establishment->getId(),
            ]),

            'manager_history' => $this->redirectToRoute('manager_history', [
                'id' => $establishment->getId(),
            ]),

            'manager_stats' => $this->redirectToRoute('manager_stats', [
                'id' => $establishment->getId(),
            ]),

            default => $this->redirectToRoute('manager_dashboard', [
                'id' => $establishment->getId(),
            ]),
        };
    }

    #[Route('/establishment/{id}/dashboard', name: 'manager_dashboard', methods: ['GET'])]
    public function dashboard(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        ServiceRepository $serviceRepository,
        UserRepository $userRepository,
        AppointmentRepository $appointmentRepository,
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);

        $servicesCount = $serviceRepository->count(['establishment' => $establishment]);
        $employeesCount = $userRepository->count(['establishment' => $establishment]);

        return $this->render('manager/dashboard.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'servicesCount' => $servicesCount,
            'employeesCount' => $employeesCount,
            'todayAppointments' => $appointmentRepository->findByEstablishmentForToday($establishment, null, 50),
        ]);
    }

    #[Route('/establishment/{id}/services', name: 'manager_services', methods: ['GET', 'POST'])]
    public function services(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $services = $serviceRepository->findBy(['establishment' => $establishment], ['id' => 'DESC']);

        $service = new Service();
        $service->setEstablishment($establishment);

        $form = $this->createForm(ServiceType::class, $service, [
            'hide_establishment' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->setEstablishment($establishment);
            $em->persist($service);
            $em->flush();

            return $this->redirectToRoute('manager_services', ['id' => $establishment->getId()]);
        }

        return $this->render('manager/services.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'services' => $services,
            'form' => $form->createView(),
            'isEdit' => false,
            'service' => null,
        ]);
    }

    #[Route('/service/{id}/edit', name: 'manager_service_edit', methods: ['GET', 'POST'])]
    public function serviceEdit(
        Service $service,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $establishment = $service->getEstablishment();
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());
        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $services = $serviceRepository->findBy(['establishment' => $establishment], ['id' => 'DESC']);

        $form = $this->createForm(ServiceType::class, $service, ['hide_establishment' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('manager_services', ['id' => $establishment->getId()]);
        }

        return $this->render('manager/services.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'services' => $services,
            'form' => $form->createView(),
            'isEdit' => true,
            'service' => $service,
        ]);
    }

    #[Route('/service/{id}/delete', name: 'manager_service_delete', methods: ['POST'])]
    public function serviceDelete(Service $service, EntityManagerInterface $em, Request $request): Response
    {
        $establishment = $service->getEstablishment();
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        if ($this->isCsrfTokenValid('delete_service_'.$service->getId(), (string) $request->request->get('_token'))) {
            $em->remove($service);
            $em->flush();
        }

        return $this->redirectToRoute('manager_services', ['id' => $establishment->getId()]);
    }

    #[Route('/establishment/{id}/employees', name: 'manager_employees', methods: ['GET'])]
    public function employees(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $employees = $userRepository->findBy(['establishment' => $establishment], ['id' => 'DESC']);

        return $this->render('manager/employees.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'employees' => $employees,
            'form' => null,
            'employee' => null,
            'isEdit' => false,
        ]);
    }

    #[Route('/establishment/{id}/employees/new', name: 'manager_employee_new', methods: ['GET', 'POST'])]
    public function employeeNew(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $employees = $userRepository->findBy(['establishment' => $establishment], ['id' => 'DESC']);

        $form = $this->createForm(ManagerEmployeeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = (array) $form->getData();
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $phone = trim((string) ($data['phone'] ?? ''));
            $isActive = (bool) ($data['isActive'] ?? true);

            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $form->addError(new FormError("Aucun utilisateur trouvé avec cet email. Il doit d’abord créer un compte sur le site."));
            } else {
                if ($user->getId() === $this->getUser()?->getId()) {
                    $form->addError(new FormError("Vous ne pouvez pas vous ajouter vous-même comme employé."));
                }

                if ($user->getEstablishment() && $user->getEstablishment()->getId() !== $establishment->getId()) {
                    $form->addError(new FormError("Cet utilisateur est déjà rattaché à un autre établissement."));
                }

                if ($user->getEstablishment() && $user->getEstablishment()->getId() === $establishment->getId()) {
                    $form->addError(new FormError("Cet utilisateur est déjà employé de cet établissement."));
                }

                if (count($form->getErrors(true)) === 0) {
                    $user->setEstablishment($establishment);
                    $user->setRoles(['ROLE_PRO']);
                    $user->setIsActive($isActive);
                    $user->setUpdateAt(new \DateTime());

                    if ($phone && !$user->getPhone()) {
                        $user->setPhone($phone);
                    }

                    $em->flush();
                    return $this->redirectToRoute('manager_employees', ['id' => $establishment->getId()]);
                }
            }
        }

        return $this->render('manager/employees.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'employees' => $employees,
            'form' => $form->createView(),
            'employee' => null,
            'isEdit' => false,
        ]);
    }

    #[Route('/employees/{id}/edit', name: 'manager_employee_edit', methods: ['GET', 'POST'])]
    public function employeeEdit(User $employee): Response
    {
        $establishment = $employee->getEstablishment();
        if (!$establishment) {
            throw $this->createNotFoundException('Employé sans établissement.');
        }

        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        return $this->redirectToRoute('manager_employees', ['id' => $establishment->getId()]);
    }

    #[Route('/employees/{id}/delete', name: 'manager_employee_delete', methods: ['POST'])]
    public function employeeDelete(User $employee, EntityManagerInterface $em, Request $request): Response
    {
        $establishment = $employee->getEstablishment();
        if (!$establishment) {
            throw $this->createNotFoundException('Employé sans établissement.');
        }

        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        if ($employee->getId() === $this->getUser()?->getId()) {
            return $this->redirectToRoute('manager_employees', ['id' => $establishment->getId()]);
        }

        if ($this->isCsrfTokenValid('delete_employee_'.$employee->getId(), (string) $request->request->get('_token'))) {
            $employee->setEstablishment(null);
            $employee->setRoles(['ROLE_CLIENT']);
            $employee->setUpdateAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('manager_employees', ['id' => $establishment->getId()]);
    }

    #[Route('/establishment/{id}/opening-hours', name: 'manager_opening_hours', methods: ['GET'])]
    public function openingHours(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        OpeningHourRepository $openingHourRepository
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $hours = $openingHourRepository->findBy(['establishment' => $establishment], ['id' => 'ASC']);

        return $this->render('manager/opening_hours.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'hours' => $hours,
            'form' => null,
            'hour' => null,
            'isEdit' => false,
        ]);
    }

    #[Route('/establishment/{id}/opening-hours/new', name: 'manager_opening_hour_new', methods: ['GET', 'POST'])]
    public function openingHourNew(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        OpeningHourRepository $openingHourRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $hours = $openingHourRepository->findBy(['establishment' => $establishment], ['id' => 'ASC']);

        $hour = new OpeningHour();
        $hour->setEstablishment($establishment);

        $form = $this->createForm(OpeningHourType::class, $hour, ['hide_establishment' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hour->setEstablishment($establishment);
            $em->persist($hour);
            $em->flush();

            return $this->redirectToRoute('manager_opening_hours', ['id' => $establishment->getId()]);
        }

        return $this->render('manager/opening_hours.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'hours' => $hours,
            'form' => $form->createView(),
            'hour' => $hour,
            'isEdit' => false,
        ]);
    }

    #[Route('/opening-hours/{id}/edit', name: 'manager_opening_hour_edit', methods: ['GET', 'POST'])]
    public function openingHourEdit(
        OpeningHour $hour,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        OpeningHourRepository $openingHourRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $establishment = $hour->getEstablishment();
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());
        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $hours = $openingHourRepository->findBy(['establishment' => $establishment], ['id' => 'ASC']);

        $form = $this->createForm(OpeningHourType::class, $hour, ['hide_establishment' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('manager_opening_hours', ['id' => $establishment->getId()]);
        }

        return $this->render('manager/opening_hours.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'hours' => $hours,
            'form' => $form->createView(),
            'hour' => $hour,
            'isEdit' => true,
        ]);
    }

    #[Route('/opening-hours/{id}/delete', name: 'manager_opening_hour_delete', methods: ['POST'])]
    public function openingHourDelete(OpeningHour $hour, EntityManagerInterface $em, Request $request): Response
    {
        $establishment = $hour->getEstablishment();
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        if ($this->isCsrfTokenValid('delete_opening_'.$hour->getId(), (string) $request->request->get('_token'))) {
            $em->remove($hour);
            $em->flush();
        }

        return $this->redirectToRoute('manager_opening_hours', ['id' => $establishment->getId()]);
    }

    #[Route('/establishment/{id}/settings', name: 'manager_settings', methods: ['GET'])]
    public function settings(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);

        return $this->render('manager/settings.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
        ]);
    }

    #[Route('/establishment/{id}/history', name: 'manager_history', methods: ['GET'])]
    public function history(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        AppointmentRepository $appointmentRepository,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $clientQuery = trim((string) $request->query->get('client', ''));
        $dateValue = trim((string) $request->query->get('date', ''));
        $dateFilter = null;

        if ($dateValue !== '') {
            try {
                $dateFilter = new \DateTimeImmutable($dateValue);
            } catch (\Throwable) {
                $dateFilter = null;
            }
        }

        $pastAppointments = $appointmentRepository->findPastByEstablishment(
            $establishment,
            $clientQuery !== '' ? $clientQuery : null,
            $dateFilter,
            200
        );

        return $this->render('manager/history.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'pastAppointments' => $pastAppointments,
            'filters' => [
                'client' => $clientQuery,
                'date' => $dateValue,
            ],
        ]);
    }

    #[Route('/establishment/{id}/stats', name: 'manager_stats', methods: ['GET'])]
    public function stats(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        AppointmentRepository $appointmentRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);
        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $owned = $establishmentRepository->findBy(['owner' => $this->getUser()], ['id' => 'DESC']);
        $stats = $this->buildEstablishmentStats($establishment, $appointmentRepository);

        return $this->render('manager/stats.html.twig', [
            'establishment' => $establishment,
            'ownedEstablishments' => $owned,
            'stats' => $stats,
            'monthlyVolumeChart' => $this->buildMonthlyVolumeChart($chartBuilder, $stats),
            'monthlyStatusChart' => $this->buildMonthlyStatusChart($chartBuilder, $stats),
            'professionalLoadChart' => $this->buildProfessionalLoadChart($chartBuilder, $stats),
        ]);
    }

    #[Route('/establishments', name: 'manager_select_establishment', methods: ['GET'])]
    public function selectEstablishments(
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_PRO');

        $user = $this->getUser();
        $owned = $establishmentRepository->findBy(['owner' => $user], ['id' => 'DESC']);

        if (!$owned) {
            throw $this->createNotFoundException("Aucun établissement assigné à ce compte gérant.");
        }

        $session->remove(self::SESSION_ACTIVE_ESTABLISHMENT);

        return $this->render('manager/select_establishment.html.twig', [
            'establishments' => $this->buildEstablishmentCards($owned),
        ]);
    }

    /**
     * @param Establishment[] $establishments
     */
    private function buildEstablishmentCards(array $establishments): array
    {
        $cards = [];

        foreach ($establishments as $establishment) {
            $cards[] = [
                'entity' => $establishment,
                'heroSrc' => $this->findHeroImageForEstablishment((int) $establishment->getId()),
            ];
        }

        return $cards;
    }

    private function findHeroImageForEstablishment(int $establishmentId): string
    {
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

    /**
     * @return array<string, mixed>
     */
    private function buildEstablishmentStats(Establishment $establishment, AppointmentRepository $appointmentRepository): array
    {
        $now = new \DateTimeImmutable();
        $monthStart = new \DateTimeImmutable('first day of this month midnight');
        $nextMonthStart = $monthStart->modify('first day of next month midnight');
        $yearStart = new \DateTimeImmutable('first day of january this year midnight');
        $nextYearStart = $yearStart->modify('first day of january next year midnight');

        $monthAppointments = $appointmentRepository->findByEstablishmentBetweenDates($establishment, $monthStart, $nextMonthStart);
        $yearAppointments = $appointmentRepository->findByEstablishmentBetweenDates($establishment, $yearStart, $nextYearStart);

        $monthSummary = [
            'total' => count($monthAppointments),
            'past' => 0,
            'upcoming' => 0,
            'cancelled' => 0,
            'confirmed' => 0,
            'pending' => 0,
        ];

        foreach ($monthAppointments as $appointment) {
            if ($appointment->getStatus() === 'cancelled') {
                ++$monthSummary['cancelled'];
                continue;
            }

            if ($appointment->getStatus() === 'confirmed') {
                ++$monthSummary['confirmed'];
            }

            if ($appointment->getStatus() === 'pending') {
                ++$monthSummary['pending'];
            }

            if ($this->isEndedAppointment($appointment, $now)) {
                ++$monthSummary['past'];
            } else {
                ++$monthSummary['upcoming'];
            }
        }

        $yearSummary = [
            'total' => count($yearAppointments),
            'past' => 0,
            'upcoming' => 0,
            'cancelled' => 0,
            'confirmed' => 0,
            'pending' => 0,
        ];

        $monthlyLabels = ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyTotals = array_fill(0, 12, 0);
        $monthlyPast = array_fill(0, 12, 0);
        $monthlyUpcoming = array_fill(0, 12, 0);
        $monthlyCancelled = array_fill(0, 12, 0);
        $professionalLoads = [];

        foreach ($yearAppointments as $appointment) {
            $date = $appointment->getDate();
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            $monthIndex = max(0, min(11, (int) $date->format('n') - 1));
            ++$monthlyTotals[$monthIndex];

            if ($appointment->getStatus() === 'cancelled') {
                ++$yearSummary['cancelled'];
                ++$monthlyCancelled[$monthIndex];
                continue;
            }

            if ($appointment->getStatus() === 'confirmed') {
                ++$yearSummary['confirmed'];
            }

            if ($appointment->getStatus() === 'pending') {
                ++$yearSummary['pending'];
            }

            if ($this->isEndedAppointment($appointment, $now)) {
                ++$yearSummary['past'];
                ++$monthlyPast[$monthIndex];
            } else {
                ++$yearSummary['upcoming'];
                ++$monthlyUpcoming[$monthIndex];
            }

            $professional = $appointment->getProfessional();
            $professionalLabel = $professional instanceof User
                ? trim(sprintf('%s %s', $professional->getFirstName() ?? '', $professional->getLastName() ?? ''))
                : 'Non assigne';

            if ($professionalLabel === '') {
                $professionalLabel = 'Non assigne';
            }

            $professionalLoads[$professionalLabel] = ($professionalLoads[$professionalLabel] ?? 0) + 1;
        }

        arsort($professionalLoads);

        return [
            'month' => $monthSummary,
            'year' => $yearSummary,
            'monthly_labels' => $monthlyLabels,
            'monthly_totals' => $monthlyTotals,
            'monthly_past' => $monthlyPast,
            'monthly_upcoming' => $monthlyUpcoming,
            'monthly_cancelled' => $monthlyCancelled,
            'professional_labels' => array_slice(array_keys($professionalLoads), 0, 8),
            'professional_values' => array_slice(array_values($professionalLoads), 0, 8),
        ];
    }

    private function buildMonthlyVolumeChart(ChartBuilderInterface $chartBuilder, array $stats): Chart
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $stats['monthly_labels'],
            'datasets' => [
                [
                    'label' => 'Realises',
                    'data' => $stats['monthly_past'],
                    'backgroundColor' => '#0f766e',
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'A venir',
                    'data' => $stats['monthly_upcoming'],
                    'backgroundColor' => '#1d4ed8',
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Annules',
                    'data' => $stats['monthly_cancelled'],
                    'backgroundColor' => '#ef4444',
                    'borderRadius' => 8,
                ],
            ],
        ]);
        $chart->setOptions($this->buildResponsiveChartOptions('Volumes mensuels'));

        return $chart;
    }

    private function buildMonthlyStatusChart(ChartBuilderInterface $chartBuilder, array $stats): Chart
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => ['Passes', 'A venir', 'Annules'],
            'datasets' => [[
                'data' => [
                    $stats['month']['past'],
                    $stats['month']['upcoming'],
                    $stats['month']['cancelled'],
                ],
                'backgroundColor' => ['#0f766e', '#1d4ed8', '#ef4444'],
                'borderWidth' => 0,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ]);

        return $chart;
    }

    private function buildProfessionalLoadChart(ChartBuilderInterface $chartBuilder, array $stats): Chart
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $stats['professional_labels'],
            'datasets' => [[
                'label' => 'Rendez-vous',
                'data' => $stats['professional_values'],
                'backgroundColor' => '#111827',
                'borderRadius' => 8,
            ]],
        ]);
        $chart->setOptions($this->buildResponsiveChartOptions('Charge par professionnel', true));

        return $chart;
    }

    private function buildResponsiveChartOptions(string $title, bool $horizontal = false): array
    {
        $scales = $horizontal
            ? [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ]
            : [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ];

        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => $horizontal ? 'y' : 'x',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'title' => [
                    'display' => false,
                    'text' => $title,
                ],
            ],
            'scales' => $scales,
        ];
    }

    private function isEndedAppointment(\App\Entity\Appointment $appointment, \DateTimeImmutable $now): bool
    {
        $endAt = $this->getAppointmentEndAt($appointment);

        return $endAt instanceof \DateTimeImmutable && $endAt <= $now;
    }

    private function getAppointmentEndAt(\App\Entity\Appointment $appointment): ?\DateTimeImmutable
    {
        $date = $appointment->getDate();
        $endTime = $appointment->getEndTime();

        if (!$date instanceof \DateTimeInterface || !$endTime instanceof \DateTimeInterface) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            sprintf('%s %s', $date->format('Y-m-d'), $endTime->format('H:i:s'))
        ) ?: null;
    }
}
