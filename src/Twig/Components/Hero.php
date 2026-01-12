<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Hero')]
final class Hero
{
    public string $title = 'Tous vos services à portée de clic';
    public string $subtitle = 'Réservez facilement vos rendez-vous chez les meilleurs professionnels de votre ville';

    public string $primaryLabel = 'Trouver un service';
    public ?string $primaryHref = null;
    public ?string $primaryIconLeft = 'uil:search';

    public string $secondaryLabel = 'Devenir partenaire';
    public ?string $secondaryHref = null;
    public ?string $secondaryIconLeft = 'uil:store';
}
