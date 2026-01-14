<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class EstablishmentDraft
{
    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $name = null;

    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Email(groups: ['step1'])]
    public ?string $professionalEmail = null;

    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $professionalPhone = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $address = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $postalCode = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $city = null;

    #[Assert\NotBlank(groups: ['step3'])]
    public ?string $description = null;
}
