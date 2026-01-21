<?php

namespace App\Dto;

use App\Entity\OpeningHour;
use App\Entity\Service;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class EstablishmentDraft
{
    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $name = null;

    #[Assert\NotBlank(groups: ['step1'])]
    public ?string $category = null;

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

    public ?string $servicesText = null;

    /** @var Collection<int, Service> */
    private Collection $services;

    /** @var Collection<int, OpeningHour> */
    private Collection $openingHours;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
    }

    /** @return Collection<int, Service> */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }
        return $this;
    }

    public function removeService(Service $service): self
    {
        $this->services->removeElement($service);
        return $this;
    }

    /** @return Collection<int, OpeningHour> */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHour $openingHour): self
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
        }
        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): self
    {
        $this->openingHours->removeElement($openingHour);
        return $this;
    }
}
