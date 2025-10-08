<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006163723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT fk_fe38f844db77003');
        $this->addSql('DROP INDEX idx_fe38f844db77003');
        $this->addSql('ALTER TABLE appointment DROP professional_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE appointment ADD professional_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT fk_fe38f844db77003 FOREIGN KEY (professional_id) REFERENCES appointment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fe38f844db77003 ON appointment (professional_id)');
    }
}
