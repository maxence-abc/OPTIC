<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116144830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8448565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FE38F8448565851 ON appointment (establishment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F8448565851');
        $this->addSql('DROP INDEX IDX_FE38F8448565851');
        $this->addSql('ALTER TABLE appointment DROP establishment_id');
    }
}
