<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountProfileType;
use App\Repository\AppointmentRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewEligibilityService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function index(
        Request $request,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        ReviewEligibilityService $reviewEligibilityService,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $activeTab = (string) $request->query->get('tab', 'reservations');
        $editProfile = $activeTab === 'profile' && $request->query->getBoolean('edit');

        $upcoming = [];
        $past = [];
        $reviewableAppointments = [];
        $clientReviews = [];
        $profileForm = null;

        if ($activeTab === 'profile') {
            $profileForm = $this->createForm(AccountProfileType::class, $user);
            $profileForm->handleRequest($request);

            if ($profileForm->isSubmitted() && $profileForm->isValid()) {
                try {
                    $entityManager->flush();
                } catch (UniqueConstraintViolationException) {
                    $profileForm->get('email')->addError(new FormError('Cette adresse email est déjà utilisée.'));
                    $editProfile = true;

                    return $this->render('account/index.html.twig', [
                        'activeTab' => $activeTab,
                        'upcomingAppointments' => $upcoming,
                        'pastAppointments' => $past,
                        'reviewableAppointments' => $reviewableAppointments,
                        'clientReviews' => $clientReviews,
                        'profileForm' => $profileForm->createView(),
                        'editProfile' => $editProfile,
                    ]);
                }

                $this->addFlash('success', 'Vos informations ont été mises à jour.');

                return $this->redirectToRoute('app_account', [
                    'tab' => 'profile',
                ]);
            }

            if ($profileForm->isSubmitted()) {
                $editProfile = true;
            }
        }

        if ($activeTab === 'reservations') {
            $upcoming = $appointmentRepository->findUpcomingForClient($user, 50);
            $past = $appointmentRepository->findPastForClient($user, 20);
        }

        if ($activeTab === 'reviews') {
            foreach ($appointmentRepository->findPastForClient($user, 100) as $appointment) {
                if (!$reviewEligibilityService->canReviewAppointment($user, $appointment)) {
                    continue;
                }

                $reviewableAppointments[] = $appointment;

                if (\count($reviewableAppointments) >= 6) {
                    break;
                }
            }

            $clientReviews = $reviewRepository->findByClient($user, 50);
        }

        return $this->render('account/index.html.twig', [
            'activeTab' => $activeTab,
            'upcomingAppointments' => $upcoming,
            'pastAppointments' => $past,
            'reviewableAppointments' => $reviewableAppointments,
            'clientReviews' => $clientReviews,
            'profileForm' => $profileForm?->createView(),
            'editProfile' => $editProfile,
        ]);
    }
}
