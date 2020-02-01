<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200129062803 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD deleted_by_id INT DEFAULT NULL, ADD date DATE DEFAULT NULL, ADD total_amount INT DEFAULT NULL, ADD ended TINYINT(1) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_commande ADD CONSTRAINT FK_422FE552B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_commande ADD CONSTRAINT FK_422FE552896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_commande ADD CONSTRAINT FK_422FE552C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_422FE552B03A8386 ON customer_commande (created_by_id)');
        $this->addSql('CREATE INDEX IDX_422FE552896DBBDE ON customer_commande (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_422FE552C76F1F52 ON customer_commande (deleted_by_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande DROP FOREIGN KEY FK_422FE552B03A8386');
        $this->addSql('ALTER TABLE customer_commande DROP FOREIGN KEY FK_422FE552896DBBDE');
        $this->addSql('ALTER TABLE customer_commande DROP FOREIGN KEY FK_422FE552C76F1F52');
        $this->addSql('DROP INDEX IDX_422FE552B03A8386 ON customer_commande');
        $this->addSql('DROP INDEX IDX_422FE552896DBBDE ON customer_commande');
        $this->addSql('DROP INDEX IDX_422FE552C76F1F52 ON customer_commande');
        $this->addSql('ALTER TABLE customer_commande DROP created_by_id, DROP updated_by_id, DROP deleted_by_id, DROP date, DROP total_amount, DROP ended, DROP created_at, DROP updated_at, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
