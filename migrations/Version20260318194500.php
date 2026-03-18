<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow multiple weekly schedule ranges per employee and day.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee_weekly_schedule ADD period_index INT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS uniq_employee_weekly_schedule');
        $this->addSql('CREATE UNIQUE INDEX uniq_employee_weekly_schedule ON employee_weekly_schedule (establishment_id, employee_id, day_of_week, period_index)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_employee_weekly_schedule');
        $this->addSql('ALTER TABLE employee_weekly_schedule DROP period_index');
        $this->addSql('CREATE UNIQUE INDEX uniq_employee_weekly_schedule ON employee_weekly_schedule (establishment_id, employee_id, day_of_week)');
    }
}
