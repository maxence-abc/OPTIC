<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
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

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        // Sécurité : un PRO ne peut créer un service que pour SON établissement
        // (Admin bypass)
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
        ]);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Service $service): Response
    {
        // Admin OK
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('service/show.html.twig', [
                'service' => $service,
            ]);
        }

        // Pro: seulement si owner de l'établissement
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
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        // Admin OK
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

        $form = $this->createForm(ServiceType::class, $service);
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
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        // Admin OK
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
