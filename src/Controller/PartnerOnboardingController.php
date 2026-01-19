<?php

namespace App\Controller;

use App\Dto\EstablishmentDraft;
use App\Entity\Establishment;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Form\PartnerStep1Type;
use App\Form\PartnerStep2Type;
use App\Form\PartnerStep3Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/partner/onboarding')]
#[IsGranted('ROLE_PRO')]
final class PartnerOnboardingController extends AbstractController
{
    private const SESSION_DRAFT = 'partner_draft';

    /**
     * Alias pour compatibilité Home: path('app_partner_onboarding', { step: 1 })
     */
    #[Route('/{step}', name: 'app_partner_onboarding', requirements: ['step' => '\d+'], methods: ['GET'])]
    public function entry(int $step = 1): Response
    {
        return match ($step) {
            1 => $this->redirectToRoute('partner_step1'),
            2 => $this->redirectToRoute('partner_step2'),
            3 => $this->redirectToRoute('partner_step3'),
            default => $this->redirectToRoute('partner_step1'),
        };
    }

    #[Route('/step-1', name: 'partner_step1', methods: ['GET', 'POST'])]
    public function step1(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $draft = $request->getSession()->get(self::SESSION_DRAFT);
        if (!$draft instanceof EstablishmentDraft) {
            $draft = new EstablishmentDraft();
        }

        $form = $this->createForm(PartnerStep1Type::class, $draft);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pas de DB ici (NOT NULL)
            $request->getSession()->set(self::SESSION_DRAFT, $draft);
            return $this->redirectToRoute('partner_step2');
        }

        return $this->render('partner_onboarding/wizard.html.twig', [
            'step' => 1,
            'form' => $form,
        ]);
    }

    #[Route('/step-2', name: 'partner_step2', methods: ['GET', 'POST'])]
    public function step2(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $draft = $request->getSession()->get(self::SESSION_DRAFT);
        if (!$draft instanceof EstablishmentDraft) {
            // si on arrive ici sans step1
            return $this->redirectToRoute('partner_step1');
        }

        // 1 ligne par défaut pour que ça s’affiche
        if ($draft->getServices()->count() === 0) {
            $draft->addService(new Service());
        }
        if ($draft->getOpeningHours()->count() === 0) {
            $draft->addOpeningHour(new OpeningHour());
        }

        $form = $this->createForm(PartnerStep2Type::class, $draft);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $request->request->has('go_back')) {
            $request->getSession()->set(self::SESSION_DRAFT, $draft);
            return $this->redirectToRoute('partner_step1');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set(self::SESSION_DRAFT, $draft);
            return $this->redirectToRoute('partner_step3');
        }

        return $this->render('partner_onboarding/wizard.html.twig', [
            'step' => 2,
            'form' => $form,
        ]);
    }

    #[Route('/step-3', name: 'partner_step3', methods: ['GET', 'POST'])]
    public function step3(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $draft = $request->getSession()->get(self::SESSION_DRAFT);
        if (!$draft instanceof EstablishmentDraft) {
            return $this->redirectToRoute('partner_step1');
        }

        $form = $this->createForm(PartnerStep3Type::class, $draft);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $request->request->has('go_back')) {
            $request->getSession()->set(self::SESSION_DRAFT, $draft);
            return $this->redirectToRoute('partner_step2');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            //  Création finale (tous les champs NOT NULL sont présents)
            $establishment = new Establishment();

            // owner
            if (method_exists($establishment, 'setOwner')) {
                $establishment->setOwner($user);
            }

            // step1 fields
            if (method_exists($establishment, 'setName')) {
                $establishment->setName($draft->name);
            }
            if (method_exists($establishment, 'setProfessionalEmail')) {
                $establishment->setProfessionalEmail($draft->professionalEmail);
            }
            if (method_exists($establishment, 'setProfessionalPhone')) {
                $establishment->setProfessionalPhone($draft->professionalPhone);
            }

            // step2 fields
            if (method_exists($establishment, 'setAddress')) {
                $establishment->setAddress($draft->address);
            }
            if (method_exists($establishment, 'setPostalCode')) {
                $establishment->setPostalCode($draft->postalCode);
            }
            if (method_exists($establishment, 'setCity')) {
                $establishment->setCity($draft->city);
            }
            if (method_exists($establishment, 'setDescription')) {
                $establishment->setDescription($draft->description);
            }

            $em->persist($establishment);

            // Services
            foreach ($draft->getServices() as $service) {
                // ignore ligne vide
                if (method_exists($service, 'getName') && !$service->getName()) {
                    continue;
                }
                $service->setEstablishment($establishment);
                $em->persist($service);
            }

            // Opening hours
            foreach ($draft->getOpeningHours() as $oh) {
                if (
                    (method_exists($oh, 'getDayOfWeek') && !$oh->getDayOfWeek()) ||
                    (method_exists($oh, 'getOpenTime') && !$oh->getOpenTime()) ||
                    (method_exists($oh, 'getCloseTime') && !$oh->getCloseTime())
                ) {
                    continue;
                }
                $oh->setEstablishment($establishment);
                $em->persist($oh);
            }

            $em->flush();

            // nettoyage session
            $request->getSession()->remove(self::SESSION_DRAFT);

            return $this->redirectToRoute('app_establishment_show', [
                'id' => $establishment->getId(),
            ]);
        }

        return $this->render('partner_onboarding/wizard.html.twig', [
            'step' => 3,
            'form' => $form,
        ]);
    }
}
