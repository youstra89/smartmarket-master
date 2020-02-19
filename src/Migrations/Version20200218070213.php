<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200218070213 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE provider_echeance (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, date_echeance DATETIME NOT NULL, amount INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, date_settlement DATETIME DEFAULT NULL, amount_settlement INT DEFAULT NULL, echeance_respectee TINYINT(1) DEFAULT NULL, is_paid TINYINT(1) NOT NULL, INDEX IDX_89BE7D282EA2E54 (commande_id), INDEX IDX_89BE7D2B03A8386 (created_by_id), INDEX IDX_89BE7D2896DBBDE (updated_by_id), INDEX IDX_89BE7D2C76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE provider_echeance ADD CONSTRAINT FK_89BE7D282EA2E54 FOREIGN KEY (commande_id) REFERENCES provider_commande (id)');
        $this->addSql('ALTER TABLE provider_echeance ADD CONSTRAINT FK_89BE7D2B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE provider_echeance ADD CONSTRAINT FK_89BE7D2896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE provider_echeance ADD CONSTRAINT FK_89BE7D2C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE provider_echeance');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
