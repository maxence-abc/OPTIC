<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Entity\User;
use App\Form\ManagerEmployeeType;
use App\Form\OpeningHourType;
use App\Form\ServiceType;
use App\Repository\EstablishmentRepository;
use App\Repository\OpeningHourRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Security\Voter\EstablishmentVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

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
            'establishments' => $owned,
        ]);
    }

    #[Route('/switch/{id}', name: 'manager_switch_establishment', methods: ['POST'])]
    public function switchEstablishment(Establishment $establishment, SessionInterface $session, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EstablishmentVoter::MANAGE, $establishment);

        $session->set(self::SESSION_ACTIVE_ESTABLISHMENT, $establishment->getId());

        $referer = $request->headers->get('referer');
        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('manager_dashboard', ['id' => $establishment->getId()]);
    }

    #[Route('/establishment/{id}/dashboard', name: 'manager_dashboard', methods: ['GET'])]
    public function dashboard(
        Establishment $establishment,
        EstablishmentRepository $establishmentRepository,
        SessionInterface $session,
        ServiceRepository $serviceRepository,
        UserRepository $userRepository,
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
        ]);
    }

    // ==========================
    // SERVICES
    // ==========================
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

    // ==========================
    // EMPLOYES
    // ==========================
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

    /**
     * Tu ne veux pas de modification : on désactive l’edit et on renvoie vers la liste.
     * (ça supprime aussi ton erreur getIsActive())
     */
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

    /**
     * RETIRER (detach) : on ne supprime pas le compte, on le détache de l’établissement.
     */
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

            // rôle par défaut : adapte si besoin
            $employee->setRoles(['ROLE_CLIENT']);

            $employee->setUpdateAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('manager_employees', ['id' => $establishment->getId()]);
    }

    // ==========================
    // HORAIRES
    // ==========================
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

    // ==========================
    // SETTINGS
    // ==========================
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
}
