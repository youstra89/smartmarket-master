<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190205154635 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B361FB8DD68');
        $this->addSql('DROP INDEX IDX_943C2B361FB8DD68 ON customer_commande_details');
        $this->addSql('ALTER TABLE customer_commande_details CHANGE customer_commande_details_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B364584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_943C2B364584665A ON customer_commande_details (product_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE settlement ADD amount INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B364584665A');
        $this->addSql('DROP INDEX IDX_943C2B364584665A ON customer_commande_details');
        $this->addSql('ALTER TABLE customer_commande_details CHANGE product_id customer_commande_details_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B361FB8DD68 FOREIGN KEY (customer_commande_details_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_943C2B361FB8DD68 ON customer_commande_details (customer_commande_details_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE settlement DROP amount');
    }
}
