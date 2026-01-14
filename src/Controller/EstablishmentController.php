<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Form\EstablishmentType;
use App\Repository\EstablishmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/establishment')]
final class EstablishmentController extends AbstractController
{
    #[Route(name: 'app_establishment_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EstablishmentRepository $establishmentRepository): Response
    {
        return $this->render('establishment/index.html.twig', [
            'establishments' => $establishmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_establishment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $establishment = new Establishment();
        $form = $this->createForm(EstablishmentType::class, $establishment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($establishment);
            $entityManager->flush();

            $this->addFlash('success', 'Établissement créé avec succès');

            return $this->redirectToRoute('app_establishment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('establishment/new.html.twig', [
            'establishment' => $establishment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_establishment_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Establishment $establishment): Response
    {
        // Admin : accès total
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('establishment/show.html.twig', [
                'establishment' => $establishment,
            ]);
        }

        // Pro : accès uniquement à sa boutique
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (method_exists($establishment, 'getOwner') && $establishment->getOwner() !== $user) {
            // Accès interdit si ce n'est pas le propriétaire
            throw $this->createAccessDeniedException('Accès interdit à cet établissement.');
        }

        return $this->render('establishment/show.html.twig', [
            'establishment' => $establishment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_establishment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Establishment $establishment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EstablishmentType::class, $establishment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Établissement modifié avec succès');

            return $this->redirectToRoute('app_establishment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('establishment/edit.html.twig', [
            'establishment' => $establishment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_establishment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Establishment $establishment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$establishment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($establishment);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Établissement supprimé avec succès');

        return $this->redirectToRoute('app_establishment_index', [], Response::HTTP_SEE_OTHER);
    }
}
