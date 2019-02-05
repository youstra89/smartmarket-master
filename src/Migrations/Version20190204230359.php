<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190204230359 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE customer_commande_details (id INT AUTO_INCREMENT NOT NULL, customer_commande_id INT NOT NULL, product_id INT NOT NULL, unit_price INT NOT NULL, quantity INT NOT NULL, subtotal INT NOT NULL, INDEX IDX_943C2B3681A1055C (customer_commande_id), INDEX IDX_943C2B364584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B3681A1055C FOREIGN KEY (customer_commande_id) REFERENCES customer_commande (id)');
        $this->addSql('ALTER TABLE customer_commande_details ADD CONSTRAINT FK_943C2B364584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE customer_commande_details');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT NOT NULL');
    }
}
