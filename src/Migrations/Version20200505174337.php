<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200505174337 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE compta_classe (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_D154E85BC54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compta_journal (id INT AUTO_INCREMENT NOT NULL, exercice_id INT NOT NULL, debit_id INT NOT NULL, credit_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, numero VARCHAR(255) NOT NULL, date DATETIME NOT NULL, label VARCHAR(255) NOT NULL, tva INT NOT NULL, montant INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, remarque VARCHAR(255) DEFAULT NULL, INDEX IDX_242C8A1789D40298 (exercice_id), INDEX IDX_242C8A17444E82EE (debit_id), INDEX IDX_242C8A17CE062FF9 (credit_id), INDEX IDX_242C8A17B03A8386 (created_by_id), INDEX IDX_242C8A17896DBBDE (updated_by_id), INDEX IDX_242C8A17C76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compta_exercice (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, label VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compta_compte (id INT AUTO_INCREMENT NOT NULL, classe_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, numero INT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_912505AD8F5EA509 (classe_id), INDEX IDX_912505ADB03A8386 (created_by_id), INDEX IDX_912505AD896DBBDE (updated_by_id), INDEX IDX_912505ADC76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compta_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE compta_classe ADD CONSTRAINT FK_D154E85BC54C8C93 FOREIGN KEY (type_id) REFERENCES compta_type (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A1789D40298 FOREIGN KEY (exercice_id) REFERENCES compta_exercice (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17444E82EE FOREIGN KEY (debit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17CE062FF9 FOREIGN KEY (credit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_compte ADD CONSTRAINT FK_912505AD8F5EA509 FOREIGN KEY (classe_id) REFERENCES compta_classe (id)');
        $this->addSql('ALTER TABLE compta_compte ADD CONSTRAINT FK_912505ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_compte ADD CONSTRAINT FK_912505AD896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_compte ADD CONSTRAINT FK_912505ADC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_compte DROP FOREIGN KEY FK_912505AD8F5EA509');
        $this->addSql('ALTER TABLE compta_journal DROP FOREIGN KEY FK_242C8A1789D40298');
        $this->addSql('ALTER TABLE compta_journal DROP FOREIGN KEY FK_242C8A17444E82EE');
        $this->addSql('ALTER TABLE compta_journal DROP FOREIGN KEY FK_242C8A17CE062FF9');
        $this->addSql('ALTER TABLE compta_classe DROP FOREIGN KEY FK_D154E85BC54C8C93');
        $this->addSql('DROP TABLE compta_classe');
        $this->addSql('DROP TABLE compta_journal');
        $this->addSql('DROP TABLE compta_exercice');
        $this->addSql('DROP TABLE compta_compte');
        $this->addSql('DROP TABLE compta_type');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
