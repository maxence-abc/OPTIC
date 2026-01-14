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
    #[IsGranted('ROLE_PRO')]
    public function index(EstablishmentRepository $establishmentRepository): Response
    {
        return $this->render('establishment/index.html.twig', [
            'establishments' => $establishmentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_establishment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PRO')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $establishment = new Establishment();

        // IMPORTANT : l'établissement appartient au PRO connecté
        if (method_exists($establishment, 'setOwner')) {
            $establishment->setOwner($user);
        }

        $form = $this->createForm(EstablishmentType::class, $establishment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($establishment);
            $entityManager->flush();

            $this->addFlash('success', 'Établissement créé avec succès');

            // UX: après création, retour sur la page show
            return $this->redirectToRoute('app_establishment_show', [
                'id' => $establishment->getId(),
            ], Response::HTTP_SEE_OTHER);
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
        // Admin OK
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('establishment/show.html.twig', [
                'establishment' => $establishment,
            ]);
        }

        // PRO: uniquement si owner
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (method_exists($establishment, 'getOwner') && $establishment->getOwner() !== $user) {
            throw $this->createAccessDeniedException('Accès interdit : établissement non autorisé.');
        }

        return $this->render('establishment/show.html.twig', [
            'establishment' => $establishment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_establishment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PRO')]
    public function edit(Request $request, Establishment $establishment, EntityManagerInterface $entityManager): Response
    {
        // Admin OK, sinon owner obligatoire
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            if (method_exists($establishment, 'getOwner') && $establishment->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit : établissement non autorisé.');
            }
        }

        $form = $this->createForm(EstablishmentType::class, $establishment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Établissement modifié avec succès');

            return $this->redirectToRoute('app_establishment_show', [
                'id' => $establishment->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('establishment/edit.html.twig', [
            'establishment' => $establishment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_establishment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PRO')]
    public function delete(Request $request, Establishment $establishment, EntityManagerInterface $entityManager): Response
    {
        // Admin OK, sinon owner obligatoire
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            if (method_exists($establishment, 'getOwner') && $establishment->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit : établissement non autorisé.');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$establishment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($establishment);
            $entityManager->flush();
            $this->addFlash('success', 'Établissement supprimé avec succès');
        }

        // Après suppression, un PRO retourne typiquement sur son dashboard (à adapter)
        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}
