<?php

namespace App\Controller;

use App\Repository\EstablishmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StoreSearchController extends AbstractController
{
    #[Route('/store/search', name: 'app_store_search', methods: ['GET'])]
    public function index(Request $request, EstablishmentRepository $establishmentRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $city = trim((string) $request->query->get('city', ''));
        $category = trim((string) $request->query->get('category', 'all'));

        $establishments = $establishmentRepository->searchForListing(
            $q !== '' ? $q : null,
            $city !== '' ? $city : null,
            ($category !== '' && $category !== 'all') ? $category : null
        );

        return $this->render('store_search/index.html.twig', [
            'establishments' => $establishments,
            'filters' => [
                'q' => $q,
                'city' => $city,
                'category' => $category,
            ],
        ]);
    }
}
