<?php

namespace App\Upload;

use App\Entity\EstablishmentImage;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

final class EstablishmentImageDirectoryNamer implements DirectoryNamerInterface
{
    public function directoryName(object|array $object, PropertyMapping $mapping): string
    {
        if (!$object instanceof EstablishmentImage) {
            return 'misc';
        }

        $establishmentId = $object->getEstablishment()?->getId();

        return $establishmentId !== null ? (string) $establishmentId : 'pending';
    }
}
