<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Réservations réelles (côté client)
        $appointments = $appointmentRepository->findBy(
            ['client' => $user],
            ['date' => 'DESC', 'startTime' => 'DESC']
        );

        return $this->render('account/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }
}
    