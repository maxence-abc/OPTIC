<?php

namespace App\Service;

use App\Entity\EmployeeWeeklySchedule;
use App\Entity\User;

final class EmployeeWeeklyScheduleService
{
    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return array<int, array<int, EmployeeWeeklySchedule[]>>
     */
    public function indexByEmployeeAndDay(array $weeklySchedules): array
    {
        $index = [];

        foreach ($this->sortSchedules($weeklySchedules) as $weeklySchedule) {
            $employee = $weeklySchedule->getEmployee();
            $dayOfWeek = $weeklySchedule->getDayOfWeek();

            if (!$employee instanceof User || !$dayOfWeek) {
                continue;
            }

            $index[$employee->getId()][$dayOfWeek][] = $weeklySchedule;
        }

        return $index;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return array<int, EmployeeWeeklySchedule[]>
     */
    public function indexByDay(array $weeklySchedules): array
    {
        $index = [];

        foreach ($this->sortSchedules($weeklySchedules) as $weeklySchedule) {
            $dayOfWeek = $weeklySchedule->getDayOfWeek();
            if (!$dayOfWeek) {
                continue;
            }

            $index[$dayOfWeek][] = $weeklySchedule;
        }

        return $index;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return array<int, array<string, mixed>>
     */
    public function buildEditorRows(array $weeklySchedules): array
    {
        $byDay = $this->indexByDay($weeklySchedules);
        $rows = [];

        foreach (EmployeeWeeklySchedule::getOrderedDayNumbers() as $dayNumber) {
            $slotIndex = [];
            foreach ($byDay[$dayNumber] ?? [] as $schedule) {
                $slotIndex[$schedule->getPeriodIndex()] = $schedule;
            }

            $slots = [];
            for ($periodIndex = 1; $periodIndex <= EmployeeWeeklySchedule::MAX_PERIODS_PER_DAY; ++$periodIndex) {
                $schedule = $slotIndex[$periodIndex] ?? null;
                $slots[] = [
                    'periodIndex' => $periodIndex,
                    'start' => $schedule?->getStartTime()?->format('H:i') ?? '',
                    'end' => $schedule?->getEndTime()?->format('H:i') ?? '',
                    'summary' => $schedule instanceof EmployeeWeeklySchedule && $schedule->isConfiguredWorkingDay()
                        ? $schedule->getDisplayRange()
                        : '',
                ];
            }

            $rows[] = [
                'dayNumber' => $dayNumber,
                'label' => EmployeeWeeklySchedule::getDayLabels()[$dayNumber] ?? 'Jour',
                'shortLabel' => EmployeeWeeklySchedule::getShortDayLabels()[$dayNumber] ?? 'Jour',
                'slots' => $slots,
                'summary' => $this->formatDisplayRanges($byDay[$dayNumber] ?? []),
            ];
        }

        return $rows;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return EmployeeWeeklySchedule[]
     */
    public function getConfiguredIntervals(array $weeklySchedules): array
    {
        return array_values(array_filter(
            $this->sortSchedules($weeklySchedules),
            static fn (EmployeeWeeklySchedule $schedule): bool => $schedule->isConfiguredWorkingDay()
        ));
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     */
    public function formatDisplayRanges(array $weeklySchedules): string
    {
        $ranges = array_map(
            static fn (EmployeeWeeklySchedule $schedule): string => $schedule->getDisplayRange(),
            $this->getConfiguredIntervals($weeklySchedules)
        );

        return $ranges === [] ? 'Repos' : implode("\n", $ranges);
    }

    /**
     * @param array<int, EmployeeWeeklySchedule[]> $weeklySchedulesByDay
     */
    public function countConfiguredDays(array $weeklySchedulesByDay): int
    {
        $count = 0;

        foreach ($weeklySchedulesByDay as $daySchedules) {
            if ($this->getConfiguredIntervals($daySchedules) !== []) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     */
    public function validateIntervals(array $weeklySchedules, int $dayNumber): ?string
    {
        $intervals = $this->getConfiguredIntervals($weeklySchedules);
        $label = EmployeeWeeklySchedule::getDayLabels()[$dayNumber] ?? 'jour';

        for ($index = 0; $index < count($intervals); ++$index) {
            $current = $intervals[$index];
            $startTime = $current->getStartTime();
            $endTime = $current->getEndTime();

            if (!$startTime instanceof \DateTimeInterface || !$endTime instanceof \DateTimeInterface || $endTime <= $startTime) {
                return sprintf('Une plage du %s est invalide. Vérifiez les heures de début et de fin.', $label);
            }

            for ($otherIndex = $index + 1; $otherIndex < count($intervals); ++$otherIndex) {
                $other = $intervals[$otherIndex];
                $otherStart = $other->getStartTime();
                $otherEnd = $other->getEndTime();

                if (!$otherStart instanceof \DateTimeInterface || !$otherEnd instanceof \DateTimeInterface) {
                    continue;
                }

                if ($this->minutes($startTime) < $this->minutes($otherEnd) && $this->minutes($endTime) > $this->minutes($otherStart)) {
                    return sprintf('Les plages du %s se chevauchent. Corrigez les horaires matin/après-midi.', $label);
                }
            }
        }

        return null;
    }

    /**
     * @param EmployeeWeeklySchedule[] $weeklySchedules
     * @return EmployeeWeeklySchedule[]
     */
    private function sortSchedules(array $weeklySchedules): array
    {
        usort($weeklySchedules, function (EmployeeWeeklySchedule $left, EmployeeWeeklySchedule $right): int {
            $leftDay = $left->getDayOfWeek() ?? 99;
            $rightDay = $right->getDayOfWeek() ?? 99;

            if ($leftDay !== $rightDay) {
                return $leftDay <=> $rightDay;
            }

            if ($left->getPeriodIndex() !== $right->getPeriodIndex()) {
                return $left->getPeriodIndex() <=> $right->getPeriodIndex();
            }

            return $this->minutes($left->getStartTime()) <=> $this->minutes($right->getStartTime());
        });

        return $weeklySchedules;
    }

    private function minutes(?\DateTimeInterface $time): int
    {
        if (!$time instanceof \DateTimeInterface) {
            return PHP_INT_MAX;
        }

        return ((int) $time->format('H') * 60) + (int) $time->format('i');
    }
}
