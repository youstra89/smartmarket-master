<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508083658 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE compta_ecriture (id INT AUTO_INCREMENT NOT NULL, exercice_id INT NOT NULL, debit_id INT NOT NULL, credit_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, numero VARCHAR(255) NOT NULL, date DATETIME NOT NULL, label VARCHAR(255) NOT NULL, tva INT NOT NULL, montant INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, remarque VARCHAR(255) DEFAULT NULL, INDEX IDX_8852BE6C89D40298 (exercice_id), INDEX IDX_8852BE6C444E82EE (debit_id), INDEX IDX_8852BE6CCE062FF9 (credit_id), INDEX IDX_8852BE6CB03A8386 (created_by_id), INDEX IDX_8852BE6C896DBBDE (updated_by_id), INDEX IDX_8852BE6CC76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C89D40298 FOREIGN KEY (exercice_id) REFERENCES compta_exercice (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C444E82EE FOREIGN KEY (debit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CCE062FF9 FOREIGN KEY (credit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE compta_journal');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE compta_exercice ADD acheve TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE compta_journal (id INT AUTO_INCREMENT NOT NULL, exercice_id INT NOT NULL, debit_id INT NOT NULL, credit_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, numero VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date DATETIME NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tva INT NOT NULL, montant INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, deleted_at DATETIME DEFAULT NULL, remarque VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_242C8A1789D40298 (exercice_id), INDEX IDX_242C8A17CE062FF9 (credit_id), INDEX IDX_242C8A17896DBBDE (updated_by_id), INDEX IDX_242C8A17444E82EE (debit_id), INDEX IDX_242C8A17B03A8386 (created_by_id), INDEX IDX_242C8A17C76F1F52 (deleted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17444E82EE FOREIGN KEY (debit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A1789D40298 FOREIGN KEY (exercice_id) REFERENCES compta_exercice (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE compta_journal ADD CONSTRAINT FK_242C8A17CE062FF9 FOREIGN KEY (credit_id) REFERENCES compta_compte (id)');
        $this->addSql('DROP TABLE compta_ecriture');
        $this->addSql('ALTER TABLE compta_exercice DROP acheve');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
