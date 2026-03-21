<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\User;

final class ReviewEligibilityService
{
    public function canReviewAppointment(User $user, Appointment $appointment): bool
    {
        $client = $appointment->getClient();
        $establishment = $appointment->getService()?->getEstablishment();

        if (!$client instanceof User || !$establishment instanceof Establishment) {
            return false;
        }

        if ($client !== $user) {
            $clientId = $client->getId();
            $userId = $user->getId();

            if ($clientId === null || $userId === null || $clientId !== $userId) {
                return false;
            }
        }

        if ($appointment->getStatus() === 'cancelled') {
            return false;
        }

        if ($appointment->getReview() !== null) {
            return false;
        }

        return $this->hasAppointmentEnded($appointment);
    }

    public function canReviewEstablishmentAppointment(User $user, Establishment $establishment, Appointment $appointment): bool
    {
        if (!$this->canReviewAppointment($user, $appointment)) {
            return false;
        }

        $appointmentEstablishment = $appointment->getService()?->getEstablishment();
        if (!$appointmentEstablishment instanceof Establishment) {
            return false;
        }

        if ($appointmentEstablishment === $establishment) {
            return true;
        }

        $appointmentEstablishmentId = $appointmentEstablishment->getId();
        $establishmentId = $establishment->getId();

        if ($appointmentEstablishmentId === null || $establishmentId === null) {
            return false;
        }

        return $appointmentEstablishmentId === $establishmentId;
    }

    public function hasAppointmentEnded(Appointment $appointment, ?\DateTimeImmutable $now = null): bool
    {
        $date = $appointment->getDate();
        if (!$date instanceof \DateTimeInterface) {
            return false;
        }

        $comparisonTime = $appointment->getEndTime() ?? $appointment->getStartTime();
        if (!$comparisonTime instanceof \DateTimeInterface) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        return $this->combineDateAndTime($date, $comparisonTime) <= $now;
    }

    private function combineDateAndTime(\DateTimeInterface $date, \DateTimeInterface $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf(
            '%s %s',
            $date->format('Y-m-d'),
            $time->format('H:i:s')
        ));
    }
}
