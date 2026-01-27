<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121084414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE establishment_image (id SERIAL NOT NULL, establishment_id INT NOT NULL, path VARCHAR(255) NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_412C382A8565851 ON establishment_image (establishment_id)');
        $this->addSql('COMMENT ON COLUMN establishment_image.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE establishment_image ADD CONSTRAINT FK_412C382A8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE establishment_image DROP CONSTRAINT FK_412C382A8565851');
        $this->addSql('DROP TABLE establishment_image');
    }
}
