<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200322170644 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE provider ADD numero_compte_bancaire VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD photo VARCHAR(255) DEFAULT NULL, ADD profession VARCHAR(255) DEFAULT NULL, ADD date_naissance DATETIME DEFAULT NULL, ADD lieu_naissance VARCHAR(255) DEFAULT NULL, ADD nature_piece_identite VARCHAR(255) DEFAULT NULL, ADD numero_piece_identite VARCHAR(255) DEFAULT NULL, ADD numero_compte_bancaire VARCHAR(255) DEFAULT NULL, ADD sexe VARCHAR(255) DEFAULT NULL, ADD civilite VARCHAR(255) DEFAULT NULL, ADD date_etablissement_piece_identite DATETIME DEFAULT NULL, ADD date_expiration_piece_identite VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer DROP photo, DROP profession, DROP date_naissance, DROP lieu_naissance, DROP nature_piece_identite, DROP numero_piece_identite, DROP numero_compte_bancaire, DROP sexe, DROP civilite, DROP date_etablissement_piece_identite, DROP date_expiration_piece_identite');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider DROP numero_compte_bancaire');
    }
}
