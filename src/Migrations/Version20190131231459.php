<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190131231459 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE provider_commande_details (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, commande_id INT NOT NULL, unit_price INT NOT NULL, quantity INT NOT NULL, minimum_selling_price INT NOT NULL, fixed_amount INT NOT NULL, subtotal INT NOT NULL, INDEX IDX_E998C8374584665A (product_id), INDEX IDX_E998C83782EA2E54 (commande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE provider_commande_details ADD CONSTRAINT FK_E998C8374584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE provider_commande_details ADD CONSTRAINT FK_E998C83782EA2E54 FOREIGN KEY (commande_id) REFERENCES provider_commande (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE provider_commande_details');
    }
}
