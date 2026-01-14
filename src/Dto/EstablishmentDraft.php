<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EstablishmentDraft
{
    /* =====================
       STEP 1 – IDENTITÉ
    ===================== */

    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $name = null;

    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $type = null;

    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Email(groups: ['step1'])]
    public ?string $professionalEmail = null;

    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $professionalPhone = null;

    /* =====================
       STEP 2 – ADRESSE
    ===================== */

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $address = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $postalCode = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $city = null;

    /* =====================
       STEP 3 – CONTENU
    ===================== */

    #[Assert\NotBlank(groups: ['step3'])]
    public ?string $description = null;

    
    public ?string $services = null;
}
