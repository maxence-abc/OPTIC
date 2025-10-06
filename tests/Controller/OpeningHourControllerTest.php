<?php

namespace App\Tests\Controller;

use App\Entity\OpeningHour;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OpeningHourControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $openingHourRepository;
    private string $path = '/opening/hour/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->openingHourRepository = $this->manager->getRepository(OpeningHour::class);

        foreach ($this->openingHourRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('OpeningHour index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'opening_hour[dayOfWeek]' => 'Testing',
            'opening_hour[openTime]' => 'Testing',
            'opening_hour[closeTime]' => 'Testing',
            'opening_hour[establishment]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->openingHourRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new OpeningHour();
        $fixture->setDayOfWeek('My Title');
        $fixture->setOpenTime('My Title');
        $fixture->setCloseTime('My Title');
        $fixture->setEstablishment('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('OpeningHour');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new OpeningHour();
        $fixture->setDayOfWeek('Value');
        $fixture->setOpenTime('Value');
        $fixture->setCloseTime('Value');
        $fixture->setEstablishment('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'opening_hour[dayOfWeek]' => 'Something New',
            'opening_hour[openTime]' => 'Something New',
            'opening_hour[closeTime]' => 'Something New',
            'opening_hour[establishment]' => 'Something New',
        ]);

        self::assertResponseRedirects('/opening/hour/');

        $fixture = $this->openingHourRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDayOfWeek());
        self::assertSame('Something New', $fixture[0]->getOpenTime());
        self::assertSame('Something New', $fixture[0]->getCloseTime());
        self::assertSame('Something New', $fixture[0]->getEstablishment());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new OpeningHour();
        $fixture->setDayOfWeek('Value');
        $fixture->setOpenTime('Value');
        $fixture->setCloseTime('Value');
        $fixture->setEstablishment('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/opening/hour/');
        self::assertSame(0, $this->openingHourRepository->count([]));
    }
}
