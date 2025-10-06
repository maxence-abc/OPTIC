<?php

namespace App\Controller;

use App\Repository\EstablishmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StoreSearchController extends AbstractController
{
    #[Route('/store/search', name: 'app_store_search')]
    public function index(EstablishmentRepository $establishmentRepository): Response
    {
        // Récupère tous les établissements
        $establishments = $establishmentRepository->findAll();

        return $this->render('store_search/index.html.twig', [
            'establishments' => $establishments,
        ]);
    }
}
