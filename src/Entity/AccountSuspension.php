<?php

namespace App\Entity;

use App\Repository\AccountSuspensionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountSuspensionRepository::class)]
class AccountSuspension
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $liftedAt = null;

    #[ORM\ManyToOne(inversedBy: 'accountSuspensions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $suspendedUser = null;

    #[ORM\ManyToOne(inversedBy: 'adminSuspensions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $adminUser = null;

    // #[ORM\ManyToOne(inversedBy: 'accountSuspensions')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?User $suspendedUser = null;

    // #[ORM\ManyToOne(inversedBy: 'adminSuspensions')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?User $adminUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLiftedAt(): ?\DateTimeImmutable
    {
        return $this->liftedAt;
    }

    public function setLiftedAt(?\DateTimeImmutable $liftedAt): static
    {
        $this->liftedAt = $liftedAt;
        return $this;
    }

    // public function getSuspendedUser(): ?User
    // {
    //     return $this->suspendedUser;
    // }

    // public function setSuspendedUser(?User $suspendedUser): static
    // {
    //     $this->suspendedUser = $suspendedUser;
    //     return $this;
    // }

    // public function getAdminUser(): ?User
    // {
    //     return $this->adminUser;
    // }

    // public function setAdminUser(?User $adminUser): static
    // {
    //     $this->adminUser = $adminUser;
    //     return $this;
    // }

    public function getSuspendedUser(): ?User
    {
        return $this->suspendedUser;
    }

    public function setSuspendedUser(?User $suspendedUser): static
    {
        $this->suspendedUser = $suspendedUser;

        return $this;
    }

    public function getAdminUser(): ?User
    {
        return $this->adminUser;
    }

    public function setAdminUser(?User $adminUser): static
    {
        $this->adminUser = $adminUser;

        return $this;
    }
}
