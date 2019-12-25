<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191222185534 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_64C19C1B03A8386 ON category (created_by_id)');
        $this->addSql('CREATE INDEX IDX_64C19C1896DBBDE ON category (updated_by_id)');
        $this->addSql('ALTER TABLE commande ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DB03A8386 ON commande (created_by_id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D896DBBDE ON commande (updated_by_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE provider_order DROP FOREIGN KEY FK_AC7F26ECA582B621');
        $this->addSql('DROP INDEX UNIQ_AC7F26ECA582B621 ON provider_order');
        $this->addSql('ALTER TABLE provider_order DROP ordeer_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1896DBBDE');
        $this->addSql('DROP INDEX IDX_64C19C1B03A8386 ON category');
        $this->addSql('DROP INDEX IDX_64C19C1896DBBDE ON category');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB03A8386');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D896DBBDE');
        $this->addSql('DROP INDEX IDX_6EEAA67DB03A8386 ON commande');
        $this->addSql('DROP INDEX IDX_6EEAA67D896DBBDE ON commande');
        $this->addSql('ALTER TABLE commande DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider_order ADD ordeer_id INT NOT NULL');
        $this->addSql('ALTER TABLE provider_order ADD CONSTRAINT FK_AC7F26ECA582B621 FOREIGN KEY (ordeer_id) REFERENCES `order` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC7F26ECA582B621 ON provider_order (ordeer_id)');
    }
}
