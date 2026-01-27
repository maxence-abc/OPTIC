<?php

namespace App\Twig\Components;

use App\Entity\Establishment;
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
}
