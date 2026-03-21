<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Service\ReviewEligibilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ReviewController extends AbstractController
{
    #[Route('/reviews/{appointment}/create', name: 'app_review_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function create(
        Request $request,
        Appointment $appointment,
        ReviewEligibilityService $reviewEligibilityService,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $redirectTo = $this->sanitizeRedirectPath((string) $request->request->get('redirect_to'))
            ?? $this->generateUrl('app_account', ['tab' => 'reviews']);

        if ($appointment->getReview() !== null) {
            $this->addFlash('info', 'Vous avez déjà laissé un avis pour cette réservation.');

            return $this->redirect($redirectTo);
        }

        if (!$reviewEligibilityService->canReviewAppointment($user, $appointment)) {
            $this->addFlash('error', 'Cette réservation ne peut pas recevoir d’avis.');

            return $this->redirect($redirectTo);
        }

        $review = (new Review())
            ->setAppointment($appointment)
            ->setClient($user)
            ->setEstablishment($appointment->getService()?->getEstablishment());

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Merci de renseigner une note et un commentaire avant d’envoyer votre avis.');

            return $this->redirect($this->appendReviewQuery($redirectTo, $appointment->getId()));
        }

        $entityManager->persist($review);
        $entityManager->flush();

        $this->addFlash('success', 'Avis soumis.');

        return $this->redirect($redirectTo);
    }

    private function sanitizeRedirectPath(?string $path): ?string
    {
        if (!is_string($path) || $path === '') {
            return null;
        }

        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        $scheme = parse_url($path, PHP_URL_SCHEME);
        $host = parse_url($path, PHP_URL_HOST);

        if ($scheme !== null || $host !== null) {
            return null;
        }

        return $path;
    }

    private function appendReviewQuery(string $path, int $appointmentId): string
    {
        $fragment = parse_url($path, PHP_URL_FRAGMENT);
        $basePath = strtok($path, '#') ?: $path;
        $separator = str_contains($basePath, '?') ? '&' : '?';
        $reviewPath = sprintf('%s%sreview=%d', $basePath, $separator, $appointmentId);

        return $fragment !== null && $fragment !== '' ? $reviewPath.'#'.$fragment : $reviewPath;
    }
}
