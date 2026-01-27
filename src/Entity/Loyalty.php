<?php

namespace App\Entity;

use App\Repository\LoyaltyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoyaltyRepository::class)]
class Loyalty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $points = null;

    // #[ORM\ManyToOne(inversedBy: 'loyalties')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'loyalties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Establishment $establishment = null;

    #[ORM\ManyToOne(inversedBy: 'loyalties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    // public function getClient(): ?User
    // {
    //     return $this->client;
    // }

    // public function setClient(?User $client): static
    // {
    //     $this->client = $client;

    //     return $this;
    // }

    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }
}
