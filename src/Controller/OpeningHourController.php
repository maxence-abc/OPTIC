<?php

namespace App\Controller;

use App\Entity\OpeningHour;
use App\Entity\Establishment;
use App\Form\OpeningHourType;
use App\Repository\OpeningHourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/opening/hour')]
final class OpeningHourController extends AbstractController
{
    #[Route(name: 'app_opening_hour_index', methods: ['GET'])]
    public function index(OpeningHourRepository $openingHourRepository): Response
    {
        return $this->render('opening_hour/index.html.twig', [
            'opening_hours' => $openingHourRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_opening_hour_new', methods: ['GET', 'POST'])]
    #[Route('/new/{id}', name: 'app_opening_hour_new_for_establishment', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ?Establishment $establishment = null): Response
    {
        $openingHour = new OpeningHour();

        // Si un établissement est passé dans l’URL, on le fixe et on masque le champ
        $hideEstablishment = false;
        if ($establishment) {
            $openingHour->setEstablishment($establishment);
            $hideEstablishment = true;
        }

        $form = $this->createForm(OpeningHourType::class, $openingHour, [
            'hide_establishment' => $hideEstablishment,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($openingHour);
            $entityManager->flush();

            $this->addFlash('success', 'Horaire ajouté avec succès !');

            if ($establishment) {
                return $this->redirectToRoute('app_establishment_show', ['id' => $establishment->getId()]);
            }

            return $this->redirectToRoute('app_opening_hour_index');
        }

        return $this->render('opening_hour/new.html.twig', [
            'opening_hour' => $openingHour,
            'form' => $form,
            'establishment' => $establishment,
        ]);
    }

    #[Route('/{id}', name: 'app_opening_hour_show', methods: ['GET'])]
    public function show(OpeningHour $openingHour): Response
    {
        return $this->render('opening_hour/show.html.twig', [
            'opening_hour' => $openingHour,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_opening_hour_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OpeningHour $openingHour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OpeningHourType::class, $openingHour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Horaire mis à jour avec succès.');

            return $this->redirectToRoute('app_opening_hour_index');
        }

        return $this->render('opening_hour/edit.html.twig', [
            'opening_hour' => $openingHour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_opening_hour_delete', methods: ['POST'])]
    public function delete(Request $request, OpeningHour $openingHour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $openingHour->getId(), $request->request->get('_token'))) {
            $entityManager->remove($openingHour);
            $entityManager->flush();
            $this->addFlash('success', 'Horaire supprimé avec succès.');
        }

        return $this->redirectToRoute('app_opening_hour_index');
    }
}
