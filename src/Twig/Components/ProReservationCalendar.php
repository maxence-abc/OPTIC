<?php

namespace App\Twig\Components;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Repository\AppointmentRepository;
use App\Repository\EstablishmentRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('ProReservationCalendar')]
final class ProReservationCalendar
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $establishmentId = 0;

    #[LiveProp(writable: true)]
    public string $focusMonth = '';

    #[LiveProp(writable: true)]
    public string $selectedDate = '';

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly EstablishmentRepository $establishmentRepository
    ) {
    }

    public function mount(int $establishmentId, ?string $focusMonth = null, ?string $selectedDate = null): void
    {
        $today = new \DateTimeImmutable('today');

        $this->establishmentId = $establishmentId;
        $this->focusMonth = $focusMonth ?: $today->format('Y-m-01');
        $this->selectedDate = $selectedDate ?: $today->format('Y-m-d');
    }

    #[LiveAction]
    public function previousMonth(): void
    {
        $monthStart = $this->getFocusMonthDate()->modify('first day of previous month');
        $this->focusMonth = $monthStart->format('Y-m-01');

        if (!$this->isSelectedDateInCurrentMonth()) {
            $this->selectedDate = $monthStart->format('Y-m-d');
        }
    }

    #[LiveAction]
    public function nextMonth(): void
    {
        $monthStart = $this->getFocusMonthDate()->modify('first day of next month');
        $this->focusMonth = $monthStart->format('Y-m-01');

        if (!$this->isSelectedDateInCurrentMonth()) {
            $this->selectedDate = $monthStart->format('Y-m-d');
        }
    }

    #[LiveAction]
    public function selectDay(#[LiveArg] string $date): void
    {
        try {
            $selected = new \DateTimeImmutable($date);
        } catch (\Throwable) {
            return;
        }

        $this->selectedDate = $selected->format('Y-m-d');
        $this->focusMonth = $selected->modify('first day of this month')->format('Y-m-01');
    }

    public function getMonthLabel(): string
    {
        $monthLabels = [
            1 => 'janvier',
            2 => 'fevrier',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'decembre',
        ];

        $month = $this->getFocusMonthDate();

        return sprintf('%s %s', ucfirst($monthLabels[(int) $month->format('n')] ?? $month->format('F')), $month->format('Y'));
    }

    /**
     * @return string[]
     */
    public function getWeekdayLabels(): array
    {
        return ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarDays(): array
    {
        $monthStart = $this->getFocusMonthDate();
        $monthEnd = $monthStart->modify('last day of this month');
        $gridStart = $monthStart->modify('monday this week');
        $gridEnd = $monthEnd->modify('sunday this week');

        $appointments = $this->appointmentRepository->findByEstablishmentBetweenDates(
            $this->getEstablishment(),
            $gridStart,
            $gridEnd->modify('+1 day')
        );

        $counts = [];
        foreach ($appointments as $appointment) {
            if ($appointment->getStatus() === 'cancelled') {
                continue;
            }

            $date = $appointment->getDate();
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            $key = $date->format('Y-m-d');
            if (!isset($counts[$key])) {
                $counts[$key] = [
                    'total' => 0,
                    'pending' => 0,
                    'confirmed' => 0,
                ];
            }

            ++$counts[$key]['total'];

            if ($appointment->getStatus() === 'pending') {
                ++$counts[$key]['pending'];
            }

            if ($appointment->getStatus() === 'confirmed') {
                ++$counts[$key]['confirmed'];
            }
        }

        $days = [];
        $cursor = $gridStart;
        $today = new \DateTimeImmutable('today');
        $selected = $this->getSelectedDateObject();

        while ($cursor <= $gridEnd) {
            $key = $cursor->format('Y-m-d');
            $dayCounts = $counts[$key] ?? ['total' => 0, 'pending' => 0, 'confirmed' => 0];

            $days[] = [
                'date' => $cursor,
                'dateKey' => $key,
                'label' => $cursor->format('j'),
                'isCurrentMonth' => $cursor->format('Y-m') === $monthStart->format('Y-m'),
                'isToday' => $cursor->format('Y-m-d') === $today->format('Y-m-d'),
                'isSelected' => $cursor->format('Y-m-d') === $selected->format('Y-m-d'),
                'counts' => $dayCounts,
            ];

            $cursor = $cursor->modify('+1 day');
        }

        return $days;
    }

    /**
     * @return Appointment[]
     */
    public function getSelectedAppointments(): array
    {
        $selected = $this->getSelectedDateObject();

        return array_values(array_filter($this->appointmentRepository->findByEstablishmentBetweenDates(
            $this->getEstablishment(),
            $selected,
            $selected->modify('+1 day')
        ), static fn (Appointment $appointment): bool => $appointment->getStatus() !== 'cancelled'));
    }

    public function getSelectedDateLabel(): string
    {
        $weekdayLabels = [
            'Mon' => 'Lundi',
            'Tue' => 'Mardi',
            'Wed' => 'Mercredi',
            'Thu' => 'Jeudi',
            'Fri' => 'Vendredi',
            'Sat' => 'Samedi',
            'Sun' => 'Dimanche',
        ];
        $monthLabels = [
            'Jan' => 'janvier',
            'Feb' => 'fevrier',
            'Mar' => 'mars',
            'Apr' => 'avril',
            'May' => 'mai',
            'Jun' => 'juin',
            'Jul' => 'juillet',
            'Aug' => 'aout',
            'Sep' => 'septembre',
            'Oct' => 'octobre',
            'Nov' => 'novembre',
            'Dec' => 'decembre',
        ];

        $date = $this->getSelectedDateObject();

        return sprintf(
            '%s %s %s %s',
            $weekdayLabels[$date->format('D')] ?? $date->format('l'),
            $date->format('j'),
            $monthLabels[$date->format('M')] ?? $date->format('F'),
            $date->format('Y')
        );
    }

    private function getEstablishment(): Establishment
    {
        $establishment = $this->establishmentRepository->find($this->establishmentId);
        if (!$establishment instanceof Establishment) {
            throw new \RuntimeException('Etablissement introuvable.');
        }

        return $establishment;
    }

    private function getFocusMonthDate(): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable($this->focusMonth))->modify('first day of this month')->setTime(0, 0);
        } catch (\Throwable) {
            return new \DateTimeImmutable('first day of this month');
        }
    }

    private function getSelectedDateObject(): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable($this->selectedDate))->setTime(0, 0);
        } catch (\Throwable) {
            return new \DateTimeImmutable('today');
        }
    }

    private function isSelectedDateInCurrentMonth(): bool
    {
        return $this->getSelectedDateObject()->format('Y-m') === $this->getFocusMonthDate()->format('Y-m');
    }
}
