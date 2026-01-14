<?php

namespace App\Controller;

use App\Dto\EstablishmentDraft;
use App\Entity\Establishment;
use App\Form\PartnerStep1Type;
use App\Form\PartnerStep2Type;
use App\Form\PartnerStep3Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PRO')]
final class PartnerOnboardingController extends AbstractController
{
    private const SESSION_KEY = 'partner_onboarding_draft';
    private const SESSION_SUBMIT_KEY = 'partner_onboarding_last_submit'; // anti double submit simple

    #[Route('/devenir-partenaire/{step}', name: 'app_partner_onboarding', requirements: ['step' => '\d+'], defaults: ['step' => 1])]
    public function wizard(
        int $step,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        // 1) Sécurise le step
        if ($step < 1 || $step > 3) {
            return $this->redirectToRoute('app_partner_onboarding', ['step' => 1]);
        }

        // 2) Empêche l'accès direct à step2/step3 sans draft en session
        if ($step > 1 && !$session->has(self::SESSION_KEY)) {
            return $this->redirectToRoute('app_partner_onboarding', ['step' => 1]);
        }

        /** @var EstablishmentDraft $draft */
        $draft = $session->get(self::SESSION_KEY) ?? new EstablishmentDraft();

        // 3) Choix du formulaire selon l’étape
        $form = match ($step) {
            1 => $this->createForm(PartnerStep1Type::class, $draft),
            2 => $this->createForm(PartnerStep2Type::class, $draft),
            3 => $this->createForm(PartnerStep3Type::class, $draft), // step3 valide step1+step2+step3
        };

        $form->handleRequest($request);

        // 4) Bouton "Retour" (ne valide pas, mais sauvegarde quand même le draft)
        if ($request->isMethod('POST') && $request->request->has('go_back')) {
            $session->set(self::SESSION_KEY, $draft);

            return $this->redirectToRoute('app_partner_onboarding', [
                'step' => max(1, $step - 1),
            ]);
        }

        // 5) Submit normal
        if ($form->isSubmitted()) {
            // Anti double submit (évite double click qui crée 2 establishments)
            $now = time();
            $last = (int) ($session->get(self::SESSION_SUBMIT_KEY) ?? 0);
            if ($now === $last && $step === 3) {
                // même seconde, même step final -> on stop
                return $this->redirectToRoute('app_partner_onboarding', ['step' => 3]);
            }

            if ($form->isValid()) {
                // Sauvegarde draft en session à chaque étape validée
                $session->set(self::SESSION_KEY, $draft);

                // Étapes 1 et 2 : next
                if ($step < 3) {
                    return $this->redirectToRoute('app_partner_onboarding', [
                        'step' => $step + 1,
                    ]);
                }

                // 6) STEP 3 : garde-fou FINAL (ceinture + bretelles)
                if (
                    !$draft->name ||
                    !$draft->professionalEmail ||
                    !$draft->professionalPhone ||
                    !$draft->address ||
                    !$draft->postalCode ||
                    !$draft->city ||
                    !$draft->description
                ) {
                    // Draft incomplet => on renvoie au step 1
                    return $this->redirectToRoute('app_partner_onboarding', ['step' => 1]);
                }

                $user = $this->getUser();
                if (!$user) {
                    return $this->redirectToRoute('app_login');
                }

                $establishment = new Establishment();
                $establishment->setOwner($user);

                // On peut setter direct car garde-fou + validation step3 globale
                $establishment->setName($draft->name);
                $establishment->setProfessionalEmail($draft->professionalEmail);
                $establishment->setProfessionalPhone($draft->professionalPhone);
                $establishment->setAddress($draft->address);
                $establishment->setPostalCode($draft->postalCode);
                $establishment->setCity($draft->city);
                $establishment->setDescription($draft->description);

                $em->persist($establishment);
                $em->flush();

                // marque submit final (anti double click)
                $session->set(self::SESSION_SUBMIT_KEY, $now);

                // Nettoyage session draft
                $session->remove(self::SESSION_KEY);

                return $this->redirectToRoute('app_establishment_show', [
                    'id' => $establishment->getId(),
                ]);
            }
        }

        return $this->render('partner_onboarding/wizard.html.twig', [
            'step' => $step,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/devenir-partenaire/reset', name: 'app_partner_onboarding_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): Response
    {
        $session->remove(self::SESSION_KEY);

        return $this->redirectToRoute('app_partner_onboarding', ['step' => 1]);
    }
}
