<?php

namespace App\Tests\Service;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use App\Service\ReviewEligibilityService;
use PHPUnit\Framework\TestCase;

final class ReviewEligibilityServiceTest extends TestCase
{
    public function testPastAppointmentCanBeReviewedByItsClient(): void
    {
        $user = (new User())->setEmail('client@example.com');
        $appointment = $this->createPastAppointment($user);

        $service = new ReviewEligibilityService();

        self::assertTrue($service->canReviewAppointment($user, $appointment));
    }

    public function testCancelledAppointmentCannotBeReviewed(): void
    {
        $user = (new User())->setEmail('client@example.com');
        $appointment = $this->createPastAppointment($user)->setStatus('cancelled');

        $service = new ReviewEligibilityService();

        self::assertFalse($service->canReviewAppointment($user, $appointment));
    }

    public function testAppointmentWithExistingReviewCannotBeReviewed(): void
    {
        $user = (new User())->setEmail('client@example.com');
        $appointment = $this->createPastAppointment($user);
        $review = (new Review())
            ->setAppointment($appointment)
            ->setClient($user)
            ->setEstablishment($appointment->getService()->getEstablishment())
            ->setRating(5)
            ->setComment('Très bien.');
        $appointment->setReview($review);

        $service = new ReviewEligibilityService();

        self::assertFalse($service->canReviewAppointment($user, $appointment));
    }

    public function testAppointmentCannotBeReviewedByAnotherClient(): void
    {
        $owner = (new User())->setEmail('owner@example.com');
        $otherUser = (new User())->setEmail('other@example.com');
        $appointment = $this->createPastAppointment($owner);

        $service = new ReviewEligibilityService();

        self::assertFalse($service->canReviewAppointment($otherUser, $appointment));
    }

    public function testAppointmentMustBelongToCurrentEstablishment(): void
    {
        $user = (new User())->setEmail('client@example.com');
        $appointment = $this->createPastAppointment($user);
        $otherEstablishment = (new Establishment())
            ->setName('Autre établissement')
            ->setAddress('2 rue B')
            ->setCity('Limoges')
            ->setPostalCode('87000');

        $service = new ReviewEligibilityService();

        self::assertFalse($service->canReviewEstablishmentAppointment($user, $otherEstablishment, $appointment));
    }

    private function createPastAppointment(User $client): Appointment
    {
        $establishment = (new Establishment())
            ->setName('Test Garage')
            ->setAddress('1 rue A')
            ->setCity('Limoges')
            ->setPostalCode('87000');

        $service = (new Service())
            ->setName('Vidange')
            ->setDuration(30)
            ->setPrice('49.00')
            ->setEstablishment($establishment);

        return (new Appointment())
            ->setClient($client)
            ->setService($service)
            ->setStatus('confirmed')
            ->setDate(new \DateTime('-2 days'))
            ->setStartTime(new \DateTime('10:00'))
            ->setEndTime(new \DateTime('10:30'));
    }
}
