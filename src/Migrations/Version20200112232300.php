<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200112232300 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE type_depense ADD created_by_id INT NOT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1C24F8A2B03A8386 ON type_depense (created_by_id)');
        $this->addSql('CREATE INDEX IDX_1C24F8A2896DBBDE ON type_depense (updated_by_id)');
        $this->addSql('ALTER TABLE mark ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F271B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F271896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6674F271B03A8386 ON mark (created_by_id)');
        $this->addSql('CREATE INDEX IDX_6674F271896DBBDE ON mark (updated_by_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F271B03A8386');
        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F271896DBBDE');
        $this->addSql('DROP INDEX IDX_6674F271B03A8386 ON mark');
        $this->addSql('DROP INDEX IDX_6674F271896DBBDE ON mark');
        $this->addSql('ALTER TABLE mark DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2B03A8386');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2896DBBDE');
        $this->addSql('DROP INDEX IDX_1C24F8A2B03A8386 ON type_depense');
        $this->addSql('DROP INDEX IDX_1C24F8A2896DBBDE ON type_depense');
        $this->addSql('ALTER TABLE type_depense DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
    }
}
