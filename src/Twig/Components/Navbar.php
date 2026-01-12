<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Navbar')]
final class Navbar
{
    /**
     * Permet de désactiver la navbar sur certaines pages si besoin
     */
    public bool $enabled = true;

    // Brand
    public ?string $brandHref = null;
    public string $brandLabel = 'OPTIC';
    public ?string $brandIcon = 'uit:calendar';

    // Links
    public ?string $homeHref = null;
    public ?string $searchHref = null;

    // Auth links
    public ?string $loginHref = null;
    public ?string $registerHref = null;

    // Labels
    public string $accountLabel = 'Mon compte';
    public string $loginLabel = 'Connexion';
    public string $registerLabel = "S'inscrire";

    // Icons
    public ?string $userIcon = 'uil:user';
}
