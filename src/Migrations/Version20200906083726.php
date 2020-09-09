<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200906083726 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product CHANGE average_purchase_price average_purchase_price DOUBLE PRECISION NOT NULL, CHANGE average_selling_price average_selling_price DOUBLE PRECISION NOT NULL, CHANGE average_package_selling_price average_package_selling_price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE compta_compte_exercice CHANGE montant_initial montant_initial DOUBLE PRECISION NOT NULL, CHANGE montant_final montant_final DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE provider_commande_details CHANGE unit_price unit_price DOUBLE PRECISION NOT NULL, CHANGE minimum_selling_price minimum_selling_price DOUBLE PRECISION NOT NULL, CHANGE fixed_amount fixed_amount DOUBLE PRECISION DEFAULT NULL, CHANGE subtotal subtotal DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_compte_exercice CHANGE montant_initial montant_initial INT NOT NULL, CHANGE montant_final montant_final INT NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE average_purchase_price average_purchase_price INT NOT NULL, CHANGE average_selling_price average_selling_price INT NOT NULL, CHANGE average_package_selling_price average_package_selling_price INT NOT NULL');
        $this->addSql('ALTER TABLE provider_commande_details CHANGE unit_price unit_price INT NOT NULL, CHANGE minimum_selling_price minimum_selling_price INT NOT NULL, CHANGE fixed_amount fixed_amount INT DEFAULT NULL, CHANGE subtotal subtotal INT NOT NULL');
    }
}
