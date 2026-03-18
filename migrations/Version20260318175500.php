<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318175500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les événements de planning employés';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE employee_schedule_event (id SERIAL NOT NULL, establishment_id INT NOT NULL, employee_id INT NOT NULL, type VARCHAR(32) NOT NULL, title VARCHAR(255) DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, start_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B0BDE9A5FF631228 ON employee_schedule_event (establishment_id)');
        $this->addSql('CREATE INDEX IDX_B0BDE9A58C03F15C ON employee_schedule_event (employee_id)');
        $this->addSql('COMMENT ON COLUMN employee_schedule_event.start_date IS \'(DC2Type:date_mutable)\'');
        $this->addSql('COMMENT ON COLUMN employee_schedule_event.end_date IS \'(DC2Type:date_mutable)\'');
        $this->addSql('COMMENT ON COLUMN employee_schedule_event.start_time IS \'(DC2Type:time_mutable)\'');
        $this->addSql('COMMENT ON COLUMN employee_schedule_event.end_time IS \'(DC2Type:time_mutable)\'');
        $this->addSql('COMMENT ON COLUMN employee_schedule_event.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE employee_schedule_event ADD CONSTRAINT FK_B0BDE9A5FF631228 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE employee_schedule_event ADD CONSTRAINT FK_B0BDE9A58C03F15C FOREIGN KEY (employee_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE employee_schedule_event');
    }
}
