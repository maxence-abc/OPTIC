<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121180005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate PostgreSQL EXCLUDE constraints to prevent overlapping appointments (professional and equipment)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');

        // IMPORTANT: EXCLUDE creates a backing GiST index; you must drop the CONSTRAINT, not the index.
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT IF EXISTS appointment_no_overlap_equipement');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT IF EXISTS appointment_no_overlap_professional');

        $this->addSql(<<<SQL
ALTER TABLE appointment
ADD CONSTRAINT appointment_no_overlap_professional
EXCLUDE USING gist (
  professional_id WITH =,
  tsrange(
    (date + start_time)::timestamp,
    (date + end_time)::timestamp,
    '[)'
  ) WITH &&
)
WHERE (professional_id IS NOT NULL)
SQL);

        $this->addSql(<<<SQL
ALTER TABLE appointment
ADD CONSTRAINT appointment_no_overlap_equipement
EXCLUDE USING gist (
  equipement_id WITH =,
  tsrange(
    (date + start_time)::timestamp,
    (date + end_time)::timestamp,
    '[)'
  ) WITH &&
)
WHERE (equipement_id IS NOT NULL)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT IF EXISTS appointment_no_overlap_equipement');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT IF EXISTS appointment_no_overlap_professional');
    }
}
