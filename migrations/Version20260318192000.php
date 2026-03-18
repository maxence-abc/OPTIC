<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create employee weekly schedules for recurring working hours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE employee_weekly_schedule (id SERIAL NOT NULL, establishment_id INT NOT NULL, employee_id INT NOT NULL, day_of_week INT NOT NULL, is_working BOOLEAN NOT NULL, start_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7C0F742FFB08B01 ON employee_weekly_schedule (establishment_id)');
        $this->addSql('CREATE INDEX IDX_7C0F74228D9F6D38 ON employee_weekly_schedule (employee_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_employee_weekly_schedule ON employee_weekly_schedule (establishment_id, employee_id, day_of_week)');
        $this->addSql('ALTER TABLE employee_weekly_schedule ADD CONSTRAINT FK_7C0F742FFB08B01 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE employee_weekly_schedule ADD CONSTRAINT FK_7C0F74228D9F6D38 FOREIGN KEY (employee_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee_weekly_schedule DROP CONSTRAINT FK_7C0F742FFB08B01');
        $this->addSql('ALTER TABLE employee_weekly_schedule DROP CONSTRAINT FK_7C0F74228D9F6D38');
        $this->addSql('DROP TABLE employee_weekly_schedule');
    }
}
