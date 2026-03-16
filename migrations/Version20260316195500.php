<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transfer tracking fields on appointments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment ADD transferred_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD transferred_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN appointment.transferred_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_FE38F8446A9C037D ON appointment (transferred_by_id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446A9C037D FOREIGN KEY (transferred_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F8446A9C037D');
        $this->addSql('DROP INDEX IDX_FE38F8446A9C037D');
        $this->addSql('ALTER TABLE appointment DROP transferred_by_id');
        $this->addSql('ALTER TABLE appointment DROP transferred_at');
    }
}
