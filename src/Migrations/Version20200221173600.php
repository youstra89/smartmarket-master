<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221173600 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE connexion (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, connected_at DATETIME NOT NULL, browser VARCHAR(255) DEFAULT NULL, operating_system VARCHAR(255) DEFAULT NULL, disconnected_at DATETIME DEFAULT NULL, ip_address VARCHAR(255) DEFAULT NULL, INDEX IDX_936BF99CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE connexion ADD CONSTRAINT FK_936BF99CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE connexion');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
