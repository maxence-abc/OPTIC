<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260109081448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop obsolete establishment_id from appointment while keeping overlap indexes';
    }

    public function up(Schema $schema): void
    {
        // On supprime uniquement ce qui concerne establishment_id
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT fk_fe38f8448565851');
        $this->addSql('DROP INDEX idx_fe38f8448565851');
        $this->addSql('ALTER TABLE appointment DROP establishment_id');

        // IMPORTANT: on ne touche pas aux index overlap
        // (appointment_no_overlap_equipment / appointment_no_overlap_pro)
    }

    public function down(Schema $schema): void
    {
        // On restaure establishment_id + FK + index
        $this->addSql('ALTER TABLE appointment ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT fk_fe38f8448565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fe38f8448565851 ON appointment (establishment_id)');

        // IMPORTANT: on ne recrée pas les index overlap ici,
        // car on ne les a jamais supprimés dans up()
    }
}
