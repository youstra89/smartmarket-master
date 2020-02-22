<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200222092157 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE type_customer');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE customer_type ADD created_by_id INT NOT NULL, ADD updated_by_id INT DEFAULT NULL, ADD deleted_by_id INT DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL, ADD type_systeme TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE customer_type ADD CONSTRAINT FK_D84FF35EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_type ADD CONSTRAINT FK_D84FF35E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_type ADD CONSTRAINT FK_D84FF35EC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D84FF35EB03A8386 ON customer_type (created_by_id)');
        $this->addSql('CREATE INDEX IDX_D84FF35E896DBBDE ON customer_type (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_D84FF35EC76F1F52 ON customer_type (deleted_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE type_customer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, type_systeme TINYINT(1) NOT NULL, INDEX IDX_4CC6A9F5C76F1F52 (deleted_by_id), INDEX IDX_4CC6A9F5B03A8386 (created_by_id), INDEX IDX_4CC6A9F5896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE type_customer ADD CONSTRAINT FK_4CC6A9F5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE type_customer ADD CONSTRAINT FK_4CC6A9F5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE type_customer ADD CONSTRAINT FK_4CC6A9F5C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_type DROP FOREIGN KEY FK_D84FF35EB03A8386');
        $this->addSql('ALTER TABLE customer_type DROP FOREIGN KEY FK_D84FF35E896DBBDE');
        $this->addSql('ALTER TABLE customer_type DROP FOREIGN KEY FK_D84FF35EC76F1F52');
        $this->addSql('DROP INDEX IDX_D84FF35EB03A8386 ON customer_type');
        $this->addSql('DROP INDEX IDX_D84FF35E896DBBDE ON customer_type');
        $this->addSql('DROP INDEX IDX_D84FF35EC76F1F52 ON customer_type');
        $this->addSql('ALTER TABLE customer_type DROP created_by_id, DROP updated_by_id, DROP deleted_by_id, DROP created_at, DROP updated_at, DROP is_deleted, DROP deleted_at, DROP type_systeme');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
