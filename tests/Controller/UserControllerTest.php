<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $userRepository;
    private string $path = '/admin/user/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->manager->getRepository(User::class);

        foreach ($this->userRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'user[firstName]' => 'Testing',
            'user[lastName]' => 'Testing',
            'user[email]' => 'Testing',
            'user[password]' => 'Testing',
            'user[phone]' => 'Testing',
            'user[role]' => 'Testing',
            'user[specialization]' => 'Testing',
            'user[isActive]' => 'Testing',
            'user[createdAt]' => 'Testing',
            'user[updateAt]' => 'Testing',
            'user[establishment]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->userRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setEmail('My Title');
        $fixture->setPassword('My Title');
        $fixture->setPhone('My Title');
        $fixture->setRole('My Title');
        $fixture->setSpecialization('My Title');
        $fixture->setIsActive('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setUpdateAt('My Title');
        $fixture->setEstablishment('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setEmail('Value');
        $fixture->setPassword('Value');
        $fixture->setPhone('Value');
        $fixture->setRole('Value');
        $fixture->setSpecialization('Value');
        $fixture->setIsActive('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdateAt('Value');
        $fixture->setEstablishment('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'user[firstName]' => 'Something New',
            'user[lastName]' => 'Something New',
            'user[email]' => 'Something New',
            'user[password]' => 'Something New',
            'user[phone]' => 'Something New',
            'user[role]' => 'Something New',
            'user[specialization]' => 'Something New',
            'user[isActive]' => 'Something New',
            'user[createdAt]' => 'Something New',
            'user[updateAt]' => 'Something New',
            'user[establishment]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/user/');

        $fixture = $this->userRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getFirstName());
        self::assertSame('Something New', $fixture[0]->getLastName());
        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getPassword());
        self::assertSame('Something New', $fixture[0]->getPhone());
        self::assertSame('Something New', $fixture[0]->getRole());
        self::assertSame('Something New', $fixture[0]->getSpecialization());
        self::assertSame('Something New', $fixture[0]->getIsActive());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getUpdateAt());
        self::assertSame('Something New', $fixture[0]->getEstablishment());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setEmail('Value');
        $fixture->setPassword('Value');
        $fixture->setPhone('Value');
        $fixture->setRole('Value');
        $fixture->setSpecialization('Value');
        $fixture->setIsActive('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdateAt('Value');
        $fixture->setEstablishment('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/user/');
        self::assertSame(0, $this->userRepository->count([]));
    }
}
