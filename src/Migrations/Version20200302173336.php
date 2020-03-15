<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200302173336 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE customer_commande_details ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD deleted_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B36B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B36896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B36C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_943C2B36B03A8386 ON customer_commande_details (created_by_id)');
        $this->addSql('CREATE INDEX IDX_943C2B36896DBBDE ON customer_commande_details (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_943C2B36C76F1F52 ON customer_commande_details (deleted_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B36B03A8386');
        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B36896DBBDE');
        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B36C76F1F52');
        $this->addSql('DROP INDEX IDX_943C2B36B03A8386 ON customer_commande_details');
        $this->addSql('DROP INDEX IDX_943C2B36896DBBDE ON customer_commande_details');
        $this->addSql('DROP INDEX IDX_943C2B36C76F1F52 ON customer_commande_details');
        $this->addSql('ALTER TABLE customer_commande_details DROP created_by_id, DROP updated_by_id, DROP deleted_by_id, DROP created_at, DROP updated_at, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
