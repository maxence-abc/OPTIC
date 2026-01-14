<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AuthTabs')]
final class AuthTabs
{
    public string $active = 'login';

    public ?string $loginHref = null;
    public ?string $registerHref = null;

    public string $loginLabel = 'Connexion';
    public string $registerLabel = 'Inscription';
}
