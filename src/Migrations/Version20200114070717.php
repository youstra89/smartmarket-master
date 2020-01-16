<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200114070717 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE echeance (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, date_echeance DATETIME NOT NULL, amount INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, date_settlement DATETIME DEFAULT NULL, amount_settlement INT DEFAULT NULL, echeance_respectee TINYINT(1) DEFAULT NULL, INDEX IDX_40D9893B82EA2E54 (commande_id), INDEX IDX_40D9893BB03A8386 (created_by_id), INDEX IDX_40D9893B896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE echeance ADD CONSTRAINT FK_40D9893B82EA2E54 FOREIGN KEY (commande_id) REFERENCES customer_commande (id)');
        $this->addSql('ALTER TABLE echeance ADD CONSTRAINT FK_40D9893BB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE echeance ADD CONSTRAINT FK_40D9893B896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE echeance');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
