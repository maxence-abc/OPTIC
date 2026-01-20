<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119100543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_suspension (id SERIAL NOT NULL, suspended_user_id INT NOT NULL, admin_user_id INT NOT NULL, reason TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lifted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7E34F7A41447C829 ON account_suspension (suspended_user_id)');
        $this->addSql('CREATE INDEX IDX_7E34F7A46352511C ON account_suspension (admin_user_id)');
        $this->addSql('COMMENT ON COLUMN account_suspension.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN account_suspension.lifted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE appointment (id SERIAL NOT NULL, service_id INT NOT NULL, equipement_id INT DEFAULT NULL, client_id INT NOT NULL, professional_id INT DEFAULT NULL, date DATE NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FE38F844ED5CA9E6 ON appointment (service_id)');
        $this->addSql('CREATE INDEX IDX_FE38F844806F0F5C ON appointment (equipement_id)');
        $this->addSql('CREATE INDEX IDX_FE38F84419EB6921 ON appointment (client_id)');
        $this->addSql('CREATE INDEX IDX_FE38F844DB77003 ON appointment (professional_id)');
        $this->addSql('COMMENT ON COLUMN appointment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE availability (id SERIAL NOT NULL, establishment_id INT NOT NULL, day_of_week VARCHAR(255) DEFAULT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3FB7A2BF8565851 ON availability (establishment_id)');
        $this->addSql('CREATE TABLE equipement (id SERIAL NOT NULL, establishment_id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B8B4C6F38565851 ON equipement (establishment_id)');
        $this->addSql('CREATE TABLE establishment (id SERIAL NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, postal_code VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, professional_email VARCHAR(255) DEFAULT NULL, professional_phone VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DBEFB1EE7E3C61F9 ON establishment (owner_id)');
        $this->addSql('CREATE TABLE loyalty (id SERIAL NOT NULL, establishment_id INT NOT NULL, client_id INT NOT NULL, points INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CC711968565851 ON loyalty (establishment_id)');
        $this->addSql('CREATE INDEX IDX_7CC7119619EB6921 ON loyalty (client_id)');
        $this->addSql('CREATE TABLE opening_hour (id SERIAL NOT NULL, establishment_id INT NOT NULL, day_of_week VARCHAR(255) NOT NULL, open_time TIME(0) WITHOUT TIME ZONE NOT NULL, close_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_969BD7658565851 ON opening_hour (establishment_id)');
        $this->addSql('CREATE TABLE service (id SERIAL NOT NULL, establishment_id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, duration INT NOT NULL, price NUMERIC(10, 2) NOT NULL, buffer_time INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E19D9AD28565851 ON service (establishment_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, establishment_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, specialization VARCHAR(255) DEFAULT NULL, is_active BOOLEAN DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, update_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D93D6498565851 ON "user" (establishment_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_log (id SERIAL NOT NULL, related_user_id INT NOT NULL, action VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, ip_adress VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6429094E98771930 ON user_log (related_user_id)');
        $this->addSql('COMMENT ON COLUMN user_log.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE account_suspension ADD CONSTRAINT FK_7E34F7A41447C829 FOREIGN KEY (suspended_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE account_suspension ADD CONSTRAINT FK_7E34F7A46352511C FOREIGN KEY (admin_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84419EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DB77003 FOREIGN KEY (professional_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BF8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F38565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE establishment ADD CONSTRAINT FK_DBEFB1EE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loyalty ADD CONSTRAINT FK_7CC711968565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loyalty ADD CONSTRAINT FK_7CC7119619EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE opening_hour ADD CONSTRAINT FK_969BD7658565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD28565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D6498565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_log ADD CONSTRAINT FK_6429094E98771930 FOREIGN KEY (related_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE account_suspension DROP CONSTRAINT FK_7E34F7A41447C829');
        $this->addSql('ALTER TABLE account_suspension DROP CONSTRAINT FK_7E34F7A46352511C');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F844ED5CA9E6');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F844806F0F5C');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F84419EB6921');
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F844DB77003');
        $this->addSql('ALTER TABLE availability DROP CONSTRAINT FK_3FB7A2BF8565851');
        $this->addSql('ALTER TABLE equipement DROP CONSTRAINT FK_B8B4C6F38565851');
        $this->addSql('ALTER TABLE establishment DROP CONSTRAINT FK_DBEFB1EE7E3C61F9');
        $this->addSql('ALTER TABLE loyalty DROP CONSTRAINT FK_7CC711968565851');
        $this->addSql('ALTER TABLE loyalty DROP CONSTRAINT FK_7CC7119619EB6921');
        $this->addSql('ALTER TABLE opening_hour DROP CONSTRAINT FK_969BD7658565851');
        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD28565851');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D6498565851');
        $this->addSql('ALTER TABLE user_log DROP CONSTRAINT FK_6429094E98771930');
        $this->addSql('DROP TABLE account_suspension');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE availability');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE establishment');
        $this->addSql('DROP TABLE loyalty');
        $this->addSql('DROP TABLE opening_hour');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_log');
    }
}
