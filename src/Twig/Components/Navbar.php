<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Navbar')]
final class Navbar
{
    public bool $enabled = true;

    public string $brandLabel = 'OPTIC';
    public ?string $brandIcon = 'uil:calendar';
    public ?string $brandHref = null;

    public ?string $homeHref = null;
    public ?string $searchHref = null;

    public string $userIcon = 'uil:user';

    // Labels
    public string $accountLabel = 'Mon compte';
    public string $loginLabel = 'Connexion';
    public string $registerLabel = 'Inscription';

    // Links
    public ?string $accountHref = null;
    public ?string $loginHref = null;
    public ?string $registerHref = null;
}
