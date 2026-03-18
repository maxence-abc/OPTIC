<?php

namespace App\Service;

use App\Entity\Establishment;
use App\Entity\OpeningHour;

final class OpeningHoursService
{
    /**
     * @var array<string, int>
     */
    private const DAY_ORDER = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];

    /**
     * @var array<string, string>
     */
    private const DAY_LABELS = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche',
    ];

    /**
     * @return array<string, string>
     */
    public function getDayLabels(): array
    {
        return self::DAY_LABELS;
    }

    /**
     * @return array<string, array{key: string, label: string, intervals: list<OpeningHour>}>
     */
    public function groupByDay(iterable $openingHours): array
    {
        $grouped = [];

        foreach (self::DAY_ORDER as $dayKey => $position) {
            $grouped[$dayKey] = [
                'key' => $dayKey,
                'label' => self::DAY_LABELS[$dayKey] ?? $dayKey,
                'position' => $position,
                'intervals' => [],
            ];
        }

        foreach ($this->sortIntervals($openingHours) as $openingHour) {
            $dayKey = $openingHour->getDayOfWeek();
            if (!$dayKey || !isset($grouped[$dayKey])) {
                continue;
            }

            $grouped[$dayKey]['intervals'][] = $openingHour;
        }

        foreach ($grouped as $dayKey => $data) {
            unset($grouped[$dayKey]['position']);
        }

        return $grouped;
    }

    /**
     * @return list<OpeningHour>
     */
    public function getIntervalsForDate(Establishment $establishment, \DateTimeInterface $date): array
    {
        return $this->getIntervalsForDay($establishment->getOpeningHours(), $date->format('l'));
    }

    /**
     * @return list<OpeningHour>
     */
    public function getIntervalsForDay(iterable $openingHours, string $dayOfWeek): array
    {
        $intervals = [];

        foreach ($openingHours as $openingHour) {
            if (!$openingHour instanceof OpeningHour || $openingHour->getDayOfWeek() !== $dayOfWeek) {
                continue;
            }

            if (!$openingHour->getOpenTime() || !$openingHour->getCloseTime()) {
                continue;
            }

            $intervals[] = $openingHour;
        }

        return $this->sortIntervals($intervals);
    }

    public function isOpenOnDate(Establishment $establishment, \DateTimeInterface $date): bool
    {
        return $this->getIntervalsForDate($establishment, $date) !== [];
    }

    public function countConfiguredDays(iterable $openingHours): int
    {
        $days = [];

        foreach ($openingHours as $openingHour) {
            if (
                !$openingHour instanceof OpeningHour
                || !$openingHour->getDayOfWeek()
                || !$openingHour->getOpenTime()
                || !$openingHour->getCloseTime()
            ) {
                continue;
            }

            $days[$openingHour->getDayOfWeek()] = true;
        }

        return count($days);
    }

    public function formatInterval(OpeningHour $openingHour): string
    {
        $openTime = $openingHour->getOpenTime();
        $closeTime = $openingHour->getCloseTime();

        if (!$openTime || !$closeTime) {
            return '';
        }

        return sprintf('%s - %s', $openTime->format('H:i'), $closeTime->format('H:i'));
    }

    public function validateInterval(OpeningHour $candidate, iterable $openingHours): ?string
    {
        $dayOfWeek = $candidate->getDayOfWeek();
        $openTime = $candidate->getOpenTime();
        $closeTime = $candidate->getCloseTime();

        if (!$dayOfWeek || !$openTime || !$closeTime) {
            return null;
        }

        $candidateOpen = $this->minutesSinceMidnight($openTime);
        $candidateClose = $this->minutesSinceMidnight($closeTime);

        if ($candidateClose <= $candidateOpen) {
            return 'La fin de plage doit être après le début de plage.';
        }

        foreach ($openingHours as $openingHour) {
            if (!$openingHour instanceof OpeningHour) {
                continue;
            }

            if ($openingHour === $candidate) {
                continue;
            }

            if (
                $candidate->getId() !== null
                && $openingHour->getId() !== null
                && $openingHour->getId() === $candidate->getId()
            ) {
                continue;
            }

            if ($openingHour->getDayOfWeek() !== $dayOfWeek) {
                continue;
            }

            $existingOpen = $openingHour->getOpenTime();
            $existingClose = $openingHour->getCloseTime();
            if (!$existingOpen || !$existingClose) {
                continue;
            }

            $existingOpenMinutes = $this->minutesSinceMidnight($existingOpen);
            $existingCloseMinutes = $this->minutesSinceMidnight($existingClose);

            if ($candidateOpen < $existingCloseMinutes && $candidateClose > $existingOpenMinutes) {
                return sprintf(
                    'Cette plage chevauche déjà %s pour %s.',
                    $this->formatInterval($openingHour),
                    self::DAY_LABELS[$dayOfWeek] ?? $dayOfWeek
                );
            }
        }

        return null;
    }

    /**
     * @return list<OpeningHour>
     */
    private function sortIntervals(iterable $openingHours): array
    {
        $intervals = [];

        foreach ($openingHours as $openingHour) {
            if ($openingHour instanceof OpeningHour) {
                $intervals[] = $openingHour;
            }
        }

        usort($intervals, function (OpeningHour $left, OpeningHour $right): int {
            $leftDay = self::DAY_ORDER[$left->getDayOfWeek() ?? ''] ?? 99;
            $rightDay = self::DAY_ORDER[$right->getDayOfWeek() ?? ''] ?? 99;

            if ($leftDay !== $rightDay) {
                return $leftDay <=> $rightDay;
            }

            $leftOpen = $left->getOpenTime();
            $rightOpen = $right->getOpenTime();
            $leftOpenMinutes = $leftOpen ? $this->minutesSinceMidnight($leftOpen) : PHP_INT_MAX;
            $rightOpenMinutes = $rightOpen ? $this->minutesSinceMidnight($rightOpen) : PHP_INT_MAX;

            if ($leftOpenMinutes !== $rightOpenMinutes) {
                return $leftOpenMinutes <=> $rightOpenMinutes;
            }

            $leftClose = $left->getCloseTime();
            $rightClose = $right->getCloseTime();
            $leftCloseMinutes = $leftClose ? $this->minutesSinceMidnight($leftClose) : PHP_INT_MAX;
            $rightCloseMinutes = $rightClose ? $this->minutesSinceMidnight($rightClose) : PHP_INT_MAX;

            return $leftCloseMinutes <=> $rightCloseMinutes;
        });

        return $intervals;
    }

    private function minutesSinceMidnight(\DateTimeInterface $time): int
    {
        return ((int) $time->format('H') * 60) + (int) $time->format('i');
    }
}
