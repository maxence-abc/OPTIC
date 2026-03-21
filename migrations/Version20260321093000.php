<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Vich upload timestamp to establishment images and normalize legacy image paths.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE establishment_image ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN establishment_image.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql("UPDATE establishment_image SET path = regexp_replace(path, '^establishments/[0-9]+/', '') WHERE path ~ '^establishments/[0-9]+/'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE establishment_image SET path = 'establishments/' || establishment_id || '/' || path WHERE path IS NOT NULL AND path <> '' AND path !~ '^establishments/[0-9]+/'");
        $this->addSql('ALTER TABLE establishment_image DROP updated_at');
    }
}
