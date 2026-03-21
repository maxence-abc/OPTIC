<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add business reply fields to reviews.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ADD business_reply TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD business_replied_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN review.business_replied_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP business_reply');
        $this->addSql('ALTER TABLE review DROP business_replied_at');
    }
}
