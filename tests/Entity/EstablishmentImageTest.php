<?php

namespace App\Tests\Entity;

use App\Entity\Establishment;
use App\Entity\EstablishmentImage;
use PHPUnit\Framework\TestCase;

final class EstablishmentImageTest extends TestCase
{
    public function testBuildsPublicPathForVichManagedImages(): void
    {
        $establishment = new Establishment();
        $this->setEntityId($establishment, 5);

        $image = new EstablishmentImage();
        $image->setEstablishment($establishment);
        $image->setPath('hero-photo.jpg');

        self::assertSame('/uploads/establishments/5/hero-photo.jpg', $image->getPublicPath());
    }

    public function testKeepsLegacyPublicPathReadable(): void
    {
        $image = new EstablishmentImage();
        $image->setPath('establishments/7/legacy-photo.jpg');

        self::assertSame('/uploads/establishments/7/legacy-photo.jpg', $image->getPublicPath());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
