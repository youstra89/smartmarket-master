<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508072738 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE compta_compte_exercice (id INT AUTO_INCREMENT NOT NULL, compte_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, montant_initial INT NOT NULL, montant_final INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_DC6C3A2FF2C56620 (compte_id), INDEX IDX_DC6C3A2FB03A8386 (created_by_id), INDEX IDX_DC6C3A2F896DBBDE (updated_by_id), INDEX IDX_DC6C3A2FC76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE compta_compte_exercice ADD CONSTRAINT FK_DC6C3A2FF2C56620 FOREIGN KEY (compte_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_compte_exercice ADD CONSTRAINT FK_DC6C3A2FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_compte_exercice ADD CONSTRAINT FK_DC6C3A2F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_compte_exercice ADD CONSTRAINT FK_DC6C3A2FC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE compta_exercice ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD deleted_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE compta_exercice ADD CONSTRAINT FK_6F43F4CAB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_exercice ADD CONSTRAINT FK_6F43F4CA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_exercice ADD CONSTRAINT FK_6F43F4CAC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6F43F4CAB03A8386 ON compta_exercice (created_by_id)');
        $this->addSql('CREATE INDEX IDX_6F43F4CA896DBBDE ON compta_exercice (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_6F43F4CAC76F1F52 ON compta_exercice (deleted_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE compta_compte_exercice');
        $this->addSql('ALTER TABLE compta_exercice DROP FOREIGN KEY FK_6F43F4CAB03A8386');
        $this->addSql('ALTER TABLE compta_exercice DROP FOREIGN KEY FK_6F43F4CA896DBBDE');
        $this->addSql('ALTER TABLE compta_exercice DROP FOREIGN KEY FK_6F43F4CAC76F1F52');
        $this->addSql('DROP INDEX IDX_6F43F4CAB03A8386 ON compta_exercice');
        $this->addSql('DROP INDEX IDX_6F43F4CA896DBBDE ON compta_exercice');
        $this->addSql('DROP INDEX IDX_6F43F4CAC76F1F52 ON compta_exercice');
        $this->addSql('ALTER TABLE compta_exercice DROP created_by_id, DROP updated_by_id, DROP deleted_by_id, DROP created_at, DROP updated_at, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
