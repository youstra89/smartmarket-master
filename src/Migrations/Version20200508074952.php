<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508074952 extends AbstractMigration
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
        $this->addSql('ALTER TABLE compta_compte_exercice ADD exercice_id INT NOT NULL');
        $this->addSql('ALTER TABLE compta_compte_exercice ADD CONSTRAINT FK_DC6C3A2F89D40298 FOREIGN KEY (exercice_id) REFERENCES compta_exercice (id)');
        $this->addSql('CREATE INDEX IDX_DC6C3A2F89D40298 ON compta_compte_exercice (exercice_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_compte_exercice DROP FOREIGN KEY FK_DC6C3A2F89D40298');
        $this->addSql('DROP INDEX IDX_DC6C3A2F89D40298 ON compta_compte_exercice');
        $this->addSql('ALTER TABLE compta_compte_exercice DROP exercice_id');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
