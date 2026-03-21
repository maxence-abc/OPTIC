<?php

namespace App\Tests\Entity;

use App\Entity\Establishment;
use App\Entity\EstablishmentImage;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

final class UserSerializationTest extends TestCase
{
    public function testUserSerializationIgnoresRelationsContainingFileObjects(): void
    {
        $user = new User();
        $user->setEmail('manager@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordvalue');
        $user->setRoles(['ROLE_PRO']);
        $user->setFirstName('Maxence');
        $user->setLastName('Abric');

        $establishment = new Establishment();
        $establishment->setName('AD Coiffure');
        $establishment->setAddress('123 rue de Paris');
        $establishment->setPostalCode('75001');
        $establishment->setCity('Paris');
        $establishment->setOwner($user);

        $image = new EstablishmentImage();
        $image->setEstablishment($establishment);
        $image->setPath('hero.jpg');
        $image->setPosition(1);
        $image->setImageFile($this->createTempImageFile());
        $establishment->addEstablishmentImage($image);

        $user->setEstablishment($establishment);

        $serialized = serialize($user);
        $restored = unserialize($serialized);

        self::assertIsString($serialized);
        self::assertInstanceOf(User::class, $restored);
        self::assertSame('manager@example.com', $restored->getEmail());
        self::assertSame('Maxence', $restored->getFirstName());
        self::assertNull($restored->getEstablishment());
    }

    private function createTempImageFile(): File
    {
        $path = tempnam(sys_get_temp_dir(), 'optic-image-');
        file_put_contents($path, 'fake-image');

        return new File($path);
    }
}
