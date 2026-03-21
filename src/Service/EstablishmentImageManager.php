<?php

namespace App\Service;

use App\Entity\Establishment;
use App\Entity\EstablishmentImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class EstablishmentImageManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function addUploadedImages(Establishment $establishment, iterable $files): int
    {
        $nextPosition = $this->getNextPosition($establishment);
        $added = 0;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $image = new EstablishmentImage();
            $establishment->addEstablishmentImage($image);
            $image->setPosition($nextPosition++);
            $image->setImageFile($file);

            $this->entityManager->persist($image);
            ++$added;
        }

        return $added;
    }

    public function reindexPositions(Establishment $establishment, ?EstablishmentImage $ignoredImage = null): void
    {
        $position = 1;

        foreach ($establishment->getEstablishmentImages() as $image) {
            if ($ignoredImage instanceof EstablishmentImage && $image === $ignoredImage) {
                continue;
            }

            $image->setPosition($position++);
        }
    }

    private function getNextPosition(Establishment $establishment): int
    {
        $maxPosition = 0;

        foreach ($establishment->getEstablishmentImages() as $image) {
            $maxPosition = max($maxPosition, (int) ($image->getPosition() ?? 0));
        }

        return $maxPosition + 1;
    }
}
