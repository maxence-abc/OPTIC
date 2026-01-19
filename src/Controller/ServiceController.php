<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\EstablishmentRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/service')]
final class ServiceController extends AbstractController
{
    #[Route(name: 'app_service_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ServiceRepository $serviceRepository): Response
    {
        return $this->render('service/index.html.twig', [
            'services' => $serviceRepository->findAll(),
        ]);
    }

    // Fallback (si tu veux garder une création "générique")
    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    // Ajuste selon ton besoin: si seuls les pros créent des services, mets ROLE_PRO
    #[IsGranted('ROLE_PRO')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $service = new Service();

        // Ici on laisse le select établissement visible
        $form = $this->createForm(ServiceType::class, $service, [
            'hide_establishment' => false,
        ]);
        $form->handleRequest($request);

        // Sécurité : PRO uniquement sur SON établissement (Admin bypass)
        if (!$this->isGranted('ROLE_ADMIN')) {
            $est = $service->getEstablishment();
            if (!$est || !method_exists($est, 'getOwner') || $est->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit : établissement invalide ou non autorisé.');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service créé avec succès !');

            return $this->redirectToRoute('app_establishment_show', [
                'id' => $service->getEstablishment()->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/new.html.twig', [
            'service' => $service,
            'form' => $form,
            'establishment' => null,
        ]);
    }

    // Route "sans friction" depuis la page établissement
    #[Route('/new/{id}', name: 'app_service_new_for_establishment', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PRO')]
    public function newForEstablishment(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Establishment $establishment = null
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$establishment) {
            throw $this->createNotFoundException('Établissement introuvable.');
        }

        // Sécurité : owner (Admin bypass)
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!method_exists($establishment, 'getOwner') || $establishment->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit : établissement non autorisé.');
            }
        }

        $service = new Service();
        $service->setEstablishment($establishment);

        // Ici on masque le champ establishment
        $form = $this->createForm(ServiceType::class, $service, [
            'hide_establishment' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service créé avec succès !');

            return $this->redirectToRoute('app_establishment_show', [
                'id' => $establishment->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/new.html.twig', [
            'service' => $service,
            'form' => $form,
            'establishment' => $establishment,
        ]);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Service $service): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('service/show.html.twig', [
                'service' => $service,
            ]);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $est = $service->getEstablishment();
        if (!$est || !method_exists($est, 'getOwner') || $est->getOwner() !== $user) {
            throw $this->createAccessDeniedException('Accès interdit à ce service.');
        }

        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PRO')]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            $est = $service->getEstablishment();
            if (!$est || !method_exists($est, 'getOwner') || $est->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit à ce service.');
            }
        }

        $form = $this->createForm(ServiceType::class, $service, [
            'hide_establishment' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Service modifié avec succès !');

            return $this->redirectToRoute('app_establishment_show', [
                'id' => $service->getEstablishment()->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PRO')]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            $est = $service->getEstablishment();
            if (!$est || !method_exists($est, 'getOwner') || $est->getOwner() !== $user) {
                throw $this->createAccessDeniedException('Accès interdit à ce service.');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();
            $this->addFlash('success', 'Service supprimé avec succès !');
        }

        return $this->redirectToRoute('app_establishment_show', [
            'id' => $service->getEstablishment()->getId(),
        ], Response::HTTP_SEE_OTHER);
    }
}
