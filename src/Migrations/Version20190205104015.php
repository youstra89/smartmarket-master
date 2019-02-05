<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190205104015 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B3681A1055C');
        $this->addSql('DROP INDEX IDX_943C2B3681A1055C ON customer_commande_details');
        $this->addSql('ALTER TABLE customer_commande_details CHANGE customer_commande_id commande_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B3682EA2E54 FOREIGN KEY (commande_id) REFERENCES customer_commande (id)');
        $this->addSql('CREATE INDEX IDX_943C2B3682EA2E54 ON customer_commande_details (commande_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_commande_details DROP FOREIGN KEY FK_943C2B3682EA2E54');
        $this->addSql('DROP INDEX IDX_943C2B3682EA2E54 ON customer_commande_details');
        $this->addSql('ALTER TABLE customer_commande_details CHANGE commande_id customer_commande_id INT NOT NULL');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B3681A1055C FOREIGN KEY (customer_commande_id) REFERENCES customer_commande (id)');
        $this->addSql('CREATE INDEX IDX_943C2B3681A1055C ON customer_commande_details (customer_commande_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
