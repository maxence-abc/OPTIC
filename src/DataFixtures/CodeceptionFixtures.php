<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

class CodeceptionFixtures extends Fixture
{
    private readonly string $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }


    public function load(ObjectManager $manager): void
    {
        $sqlFile = $this->projectDir . '/data/codeception/database_test.sql';

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException(sprintf('SQL file not found: %s', $sqlFile));
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new \RuntimeException(sprintf('Failed to read SQL file: %s', $sqlFile));
        }

        $connection = $manager->getConnection();

        try {
            $connection->beginTransaction();
            $connection->exec($sql);
            $connection->commit();
            echo "Les fixtures SQL ont été éxécutées avec succès : $sqlFile\n";
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \RuntimeException('Erreur lors de l\'exécution des fixtures SQL : ' . $e->getMessage(), 0, $e);

        }
    }
}