<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322184000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add request workflow fields to employee schedule events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE employee_schedule_event ADD status VARCHAR(32) DEFAULT 'approved' NOT NULL");
        $this->addSql('ALTER TABLE employee_schedule_event ADD requested_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee_schedule_event ADD reviewed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee_schedule_event ADD decision_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee_schedule_event ADD reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_B0BDE9A5E41D19D2 ON employee_schedule_event (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_B0BDE9A5BE84B9E9 ON employee_schedule_event (reviewed_by_id)');
        $this->addSql('CREATE INDEX IDX_B0BDE9A56BF700BD ON employee_schedule_event (status)');
        $this->addSql("COMMENT ON COLUMN employee_schedule_event.reviewed_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE employee_schedule_event ADD CONSTRAINT FK_B0BDE9A5E41D19D2 FOREIGN KEY (requested_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE employee_schedule_event ADD CONSTRAINT FK_B0BDE9A5BE84B9E9 FOREIGN KEY (reviewed_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee_schedule_event DROP CONSTRAINT FK_B0BDE9A5E41D19D2');
        $this->addSql('ALTER TABLE employee_schedule_event DROP CONSTRAINT FK_B0BDE9A5BE84B9E9');
        $this->addSql('DROP INDEX IDX_B0BDE9A5E41D19D2');
        $this->addSql('DROP INDEX IDX_B0BDE9A5BE84B9E9');
        $this->addSql('DROP INDEX IDX_B0BDE9A56BF700BD');
        $this->addSql('ALTER TABLE employee_schedule_event DROP status');
        $this->addSql('ALTER TABLE employee_schedule_event DROP requested_by_id');
        $this->addSql('ALTER TABLE employee_schedule_event DROP reviewed_by_id');
        $this->addSql('ALTER TABLE employee_schedule_event DROP decision_reason');
        $this->addSql('ALTER TABLE employee_schedule_event DROP reviewed_at');
    }
}
