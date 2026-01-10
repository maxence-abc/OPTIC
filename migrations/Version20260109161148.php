<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260109161148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: role (string) -> roles (json array) avec migration des données existantes';
    }

    public function up(Schema $schema): void
    {
        // 1) Ajout colonne roles en nullable (sinon NOT NULL casse si users existants)
        $this->addSql('ALTER TABLE "user" ADD roles JSON');

        // 2) Migrer la donnée depuis role (string) vers roles (json array)
        // Exemple: ROLE_PRO -> ["ROLE_PRO"], ROLE_ADMIN -> ["ROLE_ADMIN"]
        // Si role est NULL, on met ["ROLE_USER"]
        $this->addSql(<<<'SQL'
UPDATE "user"
SET roles = to_json(ARRAY[COALESCE(role, 'ROLE_USER')])
WHERE roles IS NULL
SQL);

        // 3) Enforcer NOT NULL après remplissage
        $this->addSql('ALTER TABLE "user" ALTER COLUMN roles SET NOT NULL');

        // 4) Drop de l'ancien champ
        $this->addSql('ALTER TABLE "user" DROP COLUMN role');
    }

    public function down(Schema $schema): void
    {
        // 1) Restaurer l’ancien champ role (string)
        $this->addSql('ALTER TABLE "user" ADD role VARCHAR(255)');

        // 2) Reconvertir roles JSON -> role string (on prend le premier rôle)
        // Si roles est NULL ou vide, on met ROLE_USER
        $this->addSql(<<<'SQL'
UPDATE "user"
SET role = COALESCE( (roles->>0), 'ROLE_USER' )
SQL);

        // 3) Rendre role NOT NULL (comme avant)
        $this->addSql('ALTER TABLE "user" ALTER COLUMN role SET NOT NULL');

        // 4) Supprimer roles
        $this->addSql('ALTER TABLE "user" DROP COLUMN roles');
    }
}
