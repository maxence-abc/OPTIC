<?php

namespace App\Entity;

use App\Repository\EmployeeWeeklyScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeWeeklyScheduleRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_employee_weekly_schedule', columns: ['establishment_id', 'employee_id', 'day_of_week'])]
class EmployeeWeeklySchedule
{
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'weeklySchedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Establishment $establishment = null;

    #[ORM\ManyToOne(inversedBy: 'weeklySchedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $employee = null;

    #[ORM\Column]
    private ?int $dayOfWeek = null;

    #[ORM\Column]
    private bool $isWorking = false;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $endTime = null;

    public static function getDayChoices(): array
    {
        return [
            'Lundi' => self::MONDAY,
            'Mardi' => self::TUESDAY,
            'Mercredi' => self::WEDNESDAY,
            'Jeudi' => self::THURSDAY,
            'Vendredi' => self::FRIDAY,
            'Samedi' => self::SATURDAY,
            'Dimanche' => self::SUNDAY,
        ];
    }

    public static function getDayLabels(): array
    {
        return array_flip(self::getDayChoices());
    }

    public static function getShortDayLabels(): array
    {
        return [
            self::MONDAY => 'Lun',
            self::TUESDAY => 'Mar',
            self::WEDNESDAY => 'Mer',
            self::THURSDAY => 'Jeu',
            self::FRIDAY => 'Ven',
            self::SATURDAY => 'Sam',
            self::SUNDAY => 'Dim',
        ];
    }

    /**
     * @return int[]
     */
    public static function getOrderedDayNumbers(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
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

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function isWorking(): bool
    {
        return $this->isWorking;
    }

    public function setIsWorking(bool $isWorking): static
    {
        $this->isWorking = $isWorking;

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

    public function getDayLabel(): string
    {
        return self::getDayLabels()[$this->dayOfWeek ?? 0] ?? 'Jour';
    }

    public function getShortDayLabel(): string
    {
        return self::getShortDayLabels()[$this->dayOfWeek ?? 0] ?? 'Jour';
    }

    public function isConfiguredWorkingDay(): bool
    {
        return $this->isWorking
            && $this->startTime instanceof \DateTimeInterface
            && $this->endTime instanceof \DateTimeInterface
            && $this->endTime > $this->startTime;
    }

    public function getDisplayRange(): string
    {
        if (!$this->isConfiguredWorkingDay()) {
            return 'Repos';
        }

        return sprintf(
            '%s - %s',
            $this->startTime?->format('H:i') ?? '',
            $this->endTime?->format('H:i') ?? ''
        );
    }
}
