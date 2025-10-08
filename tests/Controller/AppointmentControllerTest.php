<?php

namespace App\Tests\Controller;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AppointmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $appointmentRepository;
    private string $path = '/appointment/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->appointmentRepository = $this->manager->getRepository(Appointment::class);

        foreach ($this->appointmentRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Appointment index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'appointment[date]' => 'Testing',
            'appointment[startTime]' => 'Testing',
            'appointment[endTime]' => 'Testing',
            'appointment[status]' => 'Testing',
            'appointment[createdAt]' => 'Testing',
            'appointment[client]' => 'Testing',
            'appointment[professional]' => 'Testing',
            'appointment[service]' => 'Testing',
            'appointment[equipement]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->appointmentRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Appointment();
        $fixture->setDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setEndTime('My Title');
        $fixture->setStatus('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setClient('My Title');
        $fixture->setProfessional('My Title');
        $fixture->setService('My Title');
        $fixture->setEquipement('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Appointment');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Appointment();
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setEndTime('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setClient('Value');
        $fixture->setProfessional('Value');
        $fixture->setService('Value');
        $fixture->setEquipement('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'appointment[date]' => 'Something New',
            'appointment[startTime]' => 'Something New',
            'appointment[endTime]' => 'Something New',
            'appointment[status]' => 'Something New',
            'appointment[createdAt]' => 'Something New',
            'appointment[client]' => 'Something New',
            'appointment[professional]' => 'Something New',
            'appointment[service]' => 'Something New',
            'appointment[equipement]' => 'Something New',
        ]);

        self::assertResponseRedirects('/appointment/');

        $fixture = $this->appointmentRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDate());
        self::assertSame('Something New', $fixture[0]->getStartTime());
        self::assertSame('Something New', $fixture[0]->getEndTime());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getClient());
        self::assertSame('Something New', $fixture[0]->getProfessional());
        self::assertSame('Something New', $fixture[0]->getService());
        self::assertSame('Something New', $fixture[0]->getEquipement());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Appointment();
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setEndTime('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setClient('Value');
        $fixture->setProfessional('Value');
        $fixture->setService('Value');
        $fixture->setEquipement('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/appointment/');
        self::assertSame(0, $this->appointmentRepository->count([]));
    }
}
