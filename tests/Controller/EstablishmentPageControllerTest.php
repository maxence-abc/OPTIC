<?php

namespace App\Tests\Controller;

use App\Entity\Appointment;
use App\Entity\Establishment;
use App\Entity\OpeningHour;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class EstablishmentPageControllerTest extends WebTestCase
{
    private const PREFIX = 'establishment-page-test';

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

    public function testShowIgnoresInvalidProfessionalQueryValue(): void
    {
        $this->client->request('GET', '/establishments/2?professional=professional');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Réserver un créneau', (string) $this->client->getResponse()->getContent());
    }

    public function testBookTreatsInvalidProfessionalValueAsIndifferent(): void
    {
        $clientUser = $this->manager->getRepository(User::class)->findOneBy(['email' => 'maxence@gmail.com']);

        self::assertInstanceOf(User::class, $clientUser);

        $this->client->loginUser($clientUser);

        $bookingDate = new \DateTimeImmutable('next monday');
        $service = $this->createBookableServiceForDate($bookingDate);

        $this->client->request('POST', '/establishments/'.$service->getEstablishment()?->getId().'/book', [
            '_token' => $this->csrfToken('book_appointment'),
            'service' => (string) $service->getId(),
            'date' => $bookingDate->format('Y-m-d'),
            'time' => '10:00',
            'professional' => 'professional',
        ]);

        self::assertResponseRedirects();

        $freshManager = static::getContainer()->get(EntityManagerInterface::class);
        $appointment = $freshManager->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->join('a.service', 'service')
            ->andWhere('service.id = :serviceId')
            ->setParameter('serviceId', $service->getId())
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Appointment::class, $appointment);
        self::assertSame($clientUser->getId(), $appointment->getClient()?->getId());
        self::assertSame($service->getEstablishment()?->getOwner()?->getId(), $appointment->getProfessional()?->getId());
        self::assertSame($bookingDate->format('Y-m-d'), $appointment->getDate()?->format('Y-m-d'));
        self::assertSame('10:00', $appointment->getStartTime()?->format('H:i'));
        self::assertSame('pending', $appointment->getStatus());
    }

    private function createBookableServiceForDate(\DateTimeImmutable $bookingDate): Service
    {
        $owner = (new User())
            ->setEmail(self::PREFIX.'-owner-'.uniqid('', true).'@test.local')
            ->setRoles(['ROLE_PRO'])
            ->setPassword('test-password')
            ->setFirstName('Owner')
            ->setLastName('Booking')
            ->setPhone('0600000000')
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdateAt(new \DateTime());

        $establishment = (new Establishment())
            ->setName(self::PREFIX.' establishment '.uniqid('', true))
            ->setAddress('10 rue des tests')
            ->setCity('Paris')
            ->setPostalCode('75001')
            ->setDescription('Etablissement de test')
            ->setOwner($owner);

        $service = (new Service())
            ->setEstablishment($establishment)
            ->setName(self::PREFIX.' service '.uniqid('', true))
            ->setDescription('Service de reservation')
            ->setDuration(30)
            ->setPrice('35.00')
            ->setBufferTime(0);

        $openingHour = (new OpeningHour())
            ->setEstablishment($establishment)
            ->setDayOfWeek($bookingDate->format('l'))
            ->setOpenTime(new \DateTime('10:00'))
            ->setCloseTime(new \DateTime('12:00'));

        $this->manager->persist($owner);
        $this->manager->persist($establishment);
        $this->manager->persist($service);
        $this->manager->persist($openingHour);
        $this->manager->flush();

        return $service;
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
            'DELETE FROM opening_hour WHERE establishment_id IN (SELECT id FROM establishment WHERE name LIKE :establishmentPrefix)',
            ['establishmentPrefix' => self::PREFIX.'%']
        );

        $connection->executeStatement(
            'DELETE FROM service WHERE name LIKE :servicePrefix',
            ['servicePrefix' => self::PREFIX.'%']
        );

        $connection->executeStatement(
            'DELETE FROM establishment WHERE name LIKE :establishmentPrefix',
            ['establishmentPrefix' => self::PREFIX.'%']
        );

        $connection->executeStatement(
            'DELETE FROM "user" WHERE email LIKE :userPrefix',
            ['userPrefix' => self::PREFIX.'%']
        );
    }
}
