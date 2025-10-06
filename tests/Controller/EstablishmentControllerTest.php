<?php

namespace App\Tests\Controller;

use App\Entity\Establishment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EstablishmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $establishmentRepository;
    private string $path = '/establishment/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->establishmentRepository = $this->manager->getRepository(Establishment::class);

        foreach ($this->establishmentRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Establishment index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'establishment[name]' => 'Testing',
            'establishment[address]' => 'Testing',
            'establishment[city]' => 'Testing',
            'establishment[postalCode]' => 'Testing',
            'establishment[description]' => 'Testing',
            'establishment[owner]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->establishmentRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Establishment();
        $fixture->setName('My Title');
        $fixture->setAddress('My Title');
        $fixture->setCity('My Title');
        $fixture->setPostalCode('My Title');
        $fixture->setDescription('My Title');
        $fixture->setOwner('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Establishment');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Establishment();
        $fixture->setName('Value');
        $fixture->setAddress('Value');
        $fixture->setCity('Value');
        $fixture->setPostalCode('Value');
        $fixture->setDescription('Value');
        $fixture->setOwner('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'establishment[name]' => 'Something New',
            'establishment[address]' => 'Something New',
            'establishment[city]' => 'Something New',
            'establishment[postalCode]' => 'Something New',
            'establishment[description]' => 'Something New',
            'establishment[owner]' => 'Something New',
        ]);

        self::assertResponseRedirects('/establishment/');

        $fixture = $this->establishmentRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getAddress());
        self::assertSame('Something New', $fixture[0]->getCity());
        self::assertSame('Something New', $fixture[0]->getPostalCode());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getOwner());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Establishment();
        $fixture->setName('Value');
        $fixture->setAddress('Value');
        $fixture->setCity('Value');
        $fixture->setPostalCode('Value');
        $fixture->setDescription('Value');
        $fixture->setOwner('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/establishment/');
        self::assertSame(0, $this->establishmentRepository->count([]));
    }
}
