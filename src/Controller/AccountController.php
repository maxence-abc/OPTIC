<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $activeTab = (string) $request->query->get('tab', 'reservations');

        $upcoming = [];
        $past = [];

        if ($activeTab === 'reservations') {
            $upcoming = $appointmentRepository->findUpcomingForClient($user, 50);
            $past = $appointmentRepository->findPastForClient($user, 20);
        }

        return $this->render('account/index.html.twig', [
            'activeTab' => $activeTab,
            'upcomingAppointments' => $upcoming,
            'pastAppointments' => $past,
        ]);
    }
}
