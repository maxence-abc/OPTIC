<?php

namespace App\Tests\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ProDashboardControllerTest extends WebTestCase
{
    private const PREFIX = 'pro-dashboard-test';

    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private CsrfTokenManagerInterface $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get(EntityManagerInterface::class);
        $this->csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);

        $this->cleanupTestFixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFixtures();

        parent::tearDown();
    }

    public function testProDashboardShowsOnlyCurrentEstablishmentAppointments(): void
    {
        $pro = $this->loginAsFixturePro();
        $establishment = $pro->getEstablishment();

        self::assertNotNull($establishment);

        $clientUser = $this->manager->getRepository(User::class)->findOneBy(['email' => 'maxence@gmail.com']);
        $adminOwner = $this->manager->getRepository(User::class)->findOneBy(['email' => 'user@test.com']);

        self::assertInstanceOf(User::class, $clientUser);
        self::assertInstanceOf(User::class, $adminOwner);

        $visibleService = $this->createService($establishment, self::PREFIX.' visible service '.uniqid('', true));
        $visibleAppointment = $this->createAppointment($visibleService, $clientUser, $pro, 'pending');

        $hiddenEstablishment = (new Establishment())
            ->setName(self::PREFIX.' hidden establishment '.uniqid('', true))
            ->setAddress('10 rue cachée')
            ->setCity('Paris')
            ->setPostalCode('75001')
            ->setOwner($adminOwner);

        $this->manager->persist($hiddenEstablishment);

        $hiddenProfessional = $this->createProfessional($hiddenEstablishment, self::PREFIX.'-hidden-pro-'.uniqid().'@test.local');
        $hiddenService = $this->createService($hiddenEstablishment, self::PREFIX.' hidden service '.uniqid('', true));
        $this->createAppointment($hiddenService, $clientUser, $hiddenProfessional, 'pending');

        $this->manager->flush();

        $this->client->request('GET', '/pro/entreprise');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString($visibleService->getName(), (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString($hiddenService->getName(), (string) $this->client->getResponse()->getContent());
        self::assertStringContainsString('/pro/appointment/'.$visibleAppointment->getId().'/accept', (string) $this->client->getResponse()->getContent());
    }

    public function testProCanAcceptAndCancelAppointment(): void
    {
        $pro = $this->loginAsFixturePro();
        $establishment = $pro->getEstablishment();
        $clientUser = $this->manager->getRepository(User::class)->findOneBy(['email' => 'maxence@gmail.com']);

        self::assertNotNull($establishment);
        self::assertInstanceOf(User::class, $clientUser);

        $service = $this->createService($establishment, self::PREFIX.' accept service '.uniqid('', true));
        $appointment = $this->createAppointment($service, $clientUser, $pro, 'pending');

        $this->manager->flush();

        $this->client->request('POST', '/pro/appointment/'.$appointment->getId().'/accept', [
            '_token' => $this->csrfToken('pro_accept_'.$appointment->getId()),
        ]);

        self::assertResponseRedirects('/pro/entreprise');

        $updatedAppointment = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Appointment::class)
            ->find($appointment->getId());

        self::assertInstanceOf(Appointment::class, $updatedAppointment);
        self::assertSame('confirmed', $updatedAppointment->getStatus());

        $this->client->request('POST', '/pro/appointment/'.$appointment->getId().'/cancel', [
            '_token' => $this->csrfToken('pro_cancel_'.$appointment->getId()),
        ]);

        self::assertResponseRedirects('/pro/entreprise');

        $cancelledAppointment = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Appointment::class)
            ->find($appointment->getId());

        self::assertInstanceOf(Appointment::class, $cancelledAppointment);
        self::assertSame('cancelled', $cancelledAppointment->getStatus());
    }

    public function testProCanTransferAppointmentToAnotherProfessional(): void
    {
        $pro = $this->loginAsFixturePro();
        $establishment = $pro->getEstablishment();
        $clientUser = $this->manager->getRepository(User::class)->findOneBy(['email' => 'maxence@gmail.com']);

        self::assertNotNull($establishment);
        self::assertInstanceOf(User::class, $clientUser);

        $targetProfessional = $this->createProfessional($establishment, self::PREFIX.'-transfer-pro-'.uniqid().'@test.local');
        $service = $this->createService($establishment, self::PREFIX.' transfer service '.uniqid('', true));
        $appointment = $this->createAppointment($service, $clientUser, $pro, 'pending');

        $this->manager->flush();

        $this->client->request('POST', '/pro/appointment/'.$appointment->getId().'/transfer', [
            '_token' => $this->csrfToken('pro_transfer_'.$appointment->getId()),
            'professional_id' => $targetProfessional->getId(),
        ]);

        self::assertResponseRedirects('/pro/entreprise');

        $transferredAppointment = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Appointment::class)
            ->find($appointment->getId());

        self::assertInstanceOf(Appointment::class, $transferredAppointment);
        self::assertSame($targetProfessional->getId(), $transferredAppointment->getProfessional()?->getId());
        self::assertSame('pending', $transferredAppointment->getStatus());
    }

    private function loginAsFixturePro(): User
    {
        $pro = $this->manager->getRepository(User::class)->findOneBy(['email' => 'maxence.abric87@gmail.com']);

        self::assertInstanceOf(User::class, $pro);

        $this->client->loginUser($pro);

        return $pro;
    }

    private function createService(Establishment $establishment, string $name): Service
    {
        $service = (new Service())
            ->setEstablishment($establishment)
            ->setName($name)
            ->setDescription('Service de test')
            ->setDuration(30)
            ->setPrice('35.00')
            ->setBufferTime(0);

        $this->manager->persist($service);

        return $service;
    }

    private function createProfessional(Establishment $establishment, string $email): User
    {
        $professional = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_PRO'])
            ->setPassword('test-password')
            ->setFirstName('Test')
            ->setLastName('Professional')
            ->setPhone('0600000000')
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdateAt(new \DateTime())
            ->setEstablishment($establishment);

        $this->manager->persist($professional);

        return $professional;
    }

    private function createAppointment(Service $service, User $client, User $professional, string $status): Appointment
    {
        $date = new \DateTime('+3 days');
        $date->setTime(0, 0, 0);

        $startTime = (clone $date)->setTime(10, 0, 0);
        $endTime = (clone $date)->setTime(10, 30, 0);

        $appointment = (new Appointment())
            ->setService($service)
            ->setClient($client)
            ->setProfessional($professional)
            ->setDate($date)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($appointment);

        return $appointment;
    }

    private function csrfToken(string $tokenId): string
    {
        return $this->csrfTokenManager->getToken($tokenId)->getValue();
    }

    private function cleanupTestFixtures(): void
    {
        $connection = $this->manager->getConnection();

        $connection->executeStatement(
            'DELETE FROM appointment WHERE service_id IN (SELECT id FROM service WHERE name LIKE :servicePrefix) OR professional_id IN (SELECT id FROM "user" WHERE email LIKE :userPrefix)',
            [
                'servicePrefix' => self::PREFIX.'%',
                'userPrefix' => self::PREFIX.'%',
            ]
        );

        $connection->executeStatement(
            'DELETE FROM service WHERE name LIKE :servicePrefix',
            ['servicePrefix' => self::PREFIX.'%']
        );

        $connection->executeStatement(
            'DELETE FROM "user" WHERE email LIKE :userPrefix',
            ['userPrefix' => self::PREFIX.'%']
        );

        $connection->executeStatement(
            'DELETE FROM establishment WHERE name LIKE :establishmentPrefix',
            ['establishmentPrefix' => self::PREFIX.'%']
        );
    }
}
