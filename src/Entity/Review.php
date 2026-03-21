<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'review')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Establishment $establishment = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Merci de choisir une note.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre 1 et 5.')]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Merci de laisser un commentaire.')]
    #[Assert\Length(max: 1500, maxMessage: 'Votre commentaire est trop long.')]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1500, maxMessage: 'La réponse de l’établissement est trop longue.')]
    private ?string $businessReply = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $businessRepliedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;

        if ($appointment !== null && $appointment->getReview() !== $this) {
            $appointment->setReview($this);
        }

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

    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment !== null ? trim($comment) : null;

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

    public function getBusinessReply(): ?string
    {
        return $this->businessReply;
    }

    public function setBusinessReply(?string $businessReply): static
    {
        $this->businessReply = $businessReply !== null ? trim($businessReply) : null;

        return $this;
    }

    public function getBusinessRepliedAt(): ?\DateTimeImmutable
    {
        return $this->businessRepliedAt;
    }

    public function setBusinessRepliedAt(?\DateTimeImmutable $businessRepliedAt): static
    {
        $this->businessRepliedAt = $businessRepliedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function stampCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }
}
