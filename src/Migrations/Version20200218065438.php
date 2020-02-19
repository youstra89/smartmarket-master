<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200218065438 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ProviderSettlement (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, date DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, amount INT NOT NULL, number INT NOT NULL, INDEX IDX_5B86191E82EA2E54 (commande_id), INDEX IDX_5B86191EB03A8386 (created_by_id), INDEX IDX_5B86191E896DBBDE (updated_by_id), INDEX IDX_5B86191EC76F1F52 (deleted_by_id), UNIQUE INDEX paiement_unique_par_jour (date, commande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ProviderSettlement ADD CONSTRAINT FK_5B86191E82EA2E54 FOREIGN KEY (commande_id) REFERENCES provider_commande (id)');
        $this->addSql('ALTER TABLE ProviderSettlement ADD CONSTRAINT FK_5B86191EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ProviderSettlement ADD CONSTRAINT FK_5B86191E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ProviderSettlement ADD CONSTRAINT FK_5B86191EC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ProviderSettlement');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
