<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220708081607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ALTER date_and_time TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER date_and_time DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER valid_until TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER valid_until DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE transaction ALTER date_and_time TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER date_and_time DROP DEFAULT');
        $this->addSql('ALTER TABLE transaction ALTER valid_until TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE transaction ALTER valid_until DROP DEFAULT');
    }
}
