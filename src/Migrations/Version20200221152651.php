<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221152651 extends AbstractMigration
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
        $this->addSql('ALTER TABLE user ADD updated_by_id INT DEFAULT NULL, ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649896DBBDE ON user (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649C76F1F52 ON user (deleted_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649896DBBDE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649C76F1F52');
        $this->addSql('DROP INDEX IDX_8D93D649896DBBDE ON user');
        $this->addSql('DROP INDEX IDX_8D93D649C76F1F52 ON user');
        $this->addSql('ALTER TABLE user DROP updated_by_id, DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
    }
}
