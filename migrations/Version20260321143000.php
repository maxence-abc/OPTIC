<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client establishment reviews linked to appointments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE review (id SERIAL NOT NULL, appointment_id INT NOT NULL, client_id INT NOT NULL, establishment_id INT NOT NULL, rating INT NOT NULL, comment TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C511D1320D ON review (appointment_id)');
        $this->addSql('CREATE INDEX IDX_794381C519EB6921 ON review (client_id)');
        $this->addSql('CREATE INDEX IDX_794381C53C4C7CD6 ON review (establishment_id)');
        $this->addSql("COMMENT ON COLUMN review.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C511D1320D FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C519EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C53C4C7CD6 FOREIGN KEY (establishment_id) REFERENCES establishment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C511D1320D');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C519EB6921');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C53C4C7CD6');
        $this->addSql('DROP TABLE review');
    }
}
