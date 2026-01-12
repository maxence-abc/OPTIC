<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Button')]
final class Button
{
    public string $label = 'Button';

    /**
     * Si href est défini => <a>, sinon => <button>
     */
    public ?string $href = null;

    /**
     * type du <button> (button|submit|reset)
     */
    public string $type = 'button';

    /**
     * Variants: primary | outline | ghost
     */
    public string $variant = 'primary';

    /**
     * Sizes: sm | md | lg
     * (définit padding/hauteur/typo)
     */
    public string $size = 'md';

    /**
     * Largeur: fullWidth=true => 100%
     * Sinon tu peux donner width (ex: "320px" / "100%")
     */
    public bool $fullWidth = false;
    public ?string $width = null;

    /**
     * Hauteur custom (optionnel) ex: "48px"
     * Si null => dépend de size
     */
    public ?string $height = null;

    /**
     * Icônes UX Icons (ex: "uil:user")
     */
    public ?string $iconLeft = null;
    public ?string $iconRight = null;

    public bool $disabled = false;
    public bool $loading = false;

    /**
     * Classes additionnelles
     */
    public ?string $class = null;

    /**
     * Permet d'ajouter des attributs (aria-*, data-*, etc.) directement.
     * Exemple: attributes: {'data-controller': 'navbar'}
     */
    public array $attributes = [];

    public function isLink(): bool
    {
        return !empty($this->href);
    }

    public function computedStyle(): string
    {
        $styles = [];

        if ($this->width) {
            $styles[] = 'width:' . $this->width;
        }

        if ($this->fullWidth) {
            $styles[] = 'width:100%';
        }

        if ($this->height) {
            $styles[] = 'height:' . $this->height;
        }

        return implode(';', $styles);
    }

    public function computedClass(): string
    {
        $classes = [
            'btn',
            'btn--' . $this->variant,
            'btn--' . $this->size,
        ];

        if ($this->fullWidth) {
            $classes[] = 'btn--block';
        }

        if ($this->loading) {
            $classes[] = 'is-loading';
        }

        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }
}
