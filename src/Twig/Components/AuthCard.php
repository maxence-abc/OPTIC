<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AuthCard')]
final class AuthCard
{
    public string $title = 'Bienvenue sur OPTIC';
    public ?string $subtitle = null;

    /**
     * Affiche une flèche retour en haut à droite (comme sur ton Figma).
     * Si null, pas de bouton retour.
     */
    public ?string $backHref = null;

    /**
     * Icône en haut (ex: "uit:calendar")
     */
    public ?string $topIcon = 'uit:calendar';
}
