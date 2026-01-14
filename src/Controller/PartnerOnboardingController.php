<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PartnerOnboardingController extends AbstractController
{
    #[Route('/partner/onboarding', name: 'app_partner_onboarding')]
    public function index(): Response
    {
        return $this->render('partner_onboarding/index.html.twig', [
            'controller_name' => 'PartnerOnboardingController',
        ]);
    }
}
