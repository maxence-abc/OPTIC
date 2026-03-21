<?php

namespace App\Twig\Components;

use App\Entity\Establishment;
use App\Entity\Review;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('EstablishmentCard')]
final class EstablishmentCard
{
    public Establishment $establishment;

    /**
     * URL "Voir les détails"
     */
    public string $detailsHref = '#';

    /**
     * Optionnel : URL "Réserver"
     * (si tu n’as pas encore le flux réservation, laisse null)
     */
    public ?string $bookHref = null;

    /**
     * Placeholder image (si pas de photo en base)
     */
    public string $placeholderSrc = '/images/placeholders/establishment.jpg';

    public function getReviewCount(): int
    {
        return $this->establishment->getReviews()->count();
    }

    public function getAverageRating(): float
    {
        $reviews = $this->establishment->getReviews();
        if ($reviews->isEmpty()) {
            return 0.0;
        }

        $total = 0;

        foreach ($reviews as $review) {
            if (!$review instanceof Review || $review->getRating() === null) {
                continue;
            }

            $total += $review->getRating();
        }

        return round($total / $reviews->count(), 1);
    }
}
