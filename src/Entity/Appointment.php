<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $endTime = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'appointmentsAsClient')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'appointmentsAsProfessional')]
    #[ORM\JoinColumn(nullable: false)]
    private ?self $professional = null;


    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'professional')]
    private Collection $appointmentsAsProfessional;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?Equipement $equipement = null;

    public function __construct()
    {
        $this->appointmentsAsProfessional = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getProfessional(): ?self
    {
        return $this->professional;
    }

    public function setProfessional(?self $professional): static
    {
        $this->professional = $professional;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAppointmentsAsProfessional(): Collection
    {
        return $this->appointmentsAsProfessional;
    }

    public function addAppointmentsAsProfessional(self $appointmentsAsProfessional): static
    {
        if (!$this->appointmentsAsProfessional->contains($appointmentsAsProfessional)) {
            $this->appointmentsAsProfessional->add($appointmentsAsProfessional);
            $appointmentsAsProfessional->setProfessional($this);
        }

        return $this;
    }

    public function removeAppointmentsAsProfessional(self $appointmentsAsProfessional): static
    {
        if ($this->appointmentsAsProfessional->removeElement($appointmentsAsProfessional)) {
            // set the owning side to null (unless already changed)
            if ($appointmentsAsProfessional->getProfessional() === $this) {
                $appointmentsAsProfessional->setProfessional(null);
            }
        }

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getEquipement(): ?Equipement
    {
        return $this->equipement;
    }

    public function setEquipement(?Equipement $equipement): static
    {
        $this->equipement = $equipement;

        return $this;
    }
}
