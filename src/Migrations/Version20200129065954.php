<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200129065954 extends AbstractMigration
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
        $this->addSql('ALTER TABLE provider_commande DROP FOREIGN KEY FK_26A8C89482EA2E54');
        $this->addSql('DROP INDEX UNIQ_26A8C89482EA2E54 ON provider_commande');
        $this->addSql('ALTER TABLE provider_commande DROP commande_id');
        $this->addSql('ALTER TABLE customer_commande DROP FOREIGN KEY FK_422FE55282EA2E54');
        $this->addSql('DROP INDEX UNIQ_422FE55282EA2E54 ON customer_commande');
        $this->addSql('ALTER TABLE customer_commande DROP commande_id');
        $this->addSql('ALTER TABLE settlement DROP FOREIGN KEY FK_DD9F1B5182EA2E54');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B5182EA2E54 FOREIGN KEY (commande_id) REFERENCES customer_commande (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande ADD commande_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_commande ADD CONSTRAINT FK_422FE55282EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_422FE55282EA2E54 ON customer_commande (commande_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider_commande ADD commande_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE provider_commande ADD CONSTRAINT FK_26A8C89482EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_26A8C89482EA2E54 ON provider_commande (commande_id)');
        $this->addSql('ALTER TABLE settlement DROP FOREIGN KEY FK_DD9F1B5182EA2E54');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B5182EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
    }
}
