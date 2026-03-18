<?php

namespace App\Entity;

use App\Repository\EmployeeScheduleEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeScheduleEventRepository::class)]
class EmployeeScheduleEvent
{
    public const TYPE_WORK = 'work';
    public const TYPE_REST = 'rest';
    public const TYPE_LEAVE = 'leave';
    public const TYPE_TRAINING = 'training';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scheduleEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Establishment $establishment = null;

    #[ORM\ManyToOne(inversedBy: 'scheduleEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employee = null;

    #[ORM\Column(length: 32)]
    private ?string $type = self::TYPE_WORK;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $endDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $endTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function getTypeChoices(): array
    {
        return [
            'Travail' => self::TYPE_WORK,
            'Repos' => self::TYPE_REST,
            'Congé' => self::TYPE_LEAVE,
            'Formation' => self::TYPE_TRAINING,
        ];
    }

    public static function getTypeLabels(): array
    {
        return array_flip(self::getTypeChoices());
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTime $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getTypeLabel(): string
    {
        return self::getTypeLabels()[$this->type ?? ''] ?? 'Événement';
    }

    public function isAllDay(): bool
    {
        return !$this->startTime instanceof \DateTime || !$this->endTime instanceof \DateTime;
    }

    public function occursOn(\DateTimeInterface $date): bool
    {
        $current = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $start = \DateTimeImmutable::createFromInterface($this->startDate ?? $date)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromInterface($this->endDate ?? $date)->setTime(0, 0, 0);

        return $current >= $start && $current <= $end;
    }

    public function getDisplayTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->type === self::TYPE_WORK && !$this->isAllDay()) {
            return trim(sprintf(
                '%s - %s',
                $this->startTime?->format('H:i') ?? '',
                $this->endTime?->format('H:i') ?? ''
            ));
        }

        return $this->getTypeLabel();
    }
}
