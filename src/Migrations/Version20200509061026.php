<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200509061026 extends AbstractMigration
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
        $this->addSql('ALTER TABLE compta_ecriture ADD vente_id INT DEFAULT NULL, ADD achat_id INT DEFAULT NULL, ADD reglement_client_id INT DEFAULT NULL, ADD regelement_fournisseur_id INT DEFAULT NULL, ADD depense_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C7DC7170A FOREIGN KEY (vente_id) REFERENCES customer_commande (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CFE95D117 FOREIGN KEY (achat_id) REFERENCES provider_commande (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C7970E16F FOREIGN KEY (reglement_client_id) REFERENCES settlement (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C385073AF FOREIGN KEY (regelement_fournisseur_id) REFERENCES provider_settlement (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C41D81563 FOREIGN KEY (depense_id) REFERENCES depense (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8852BE6C7DC7170A ON compta_ecriture (vente_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8852BE6CFE95D117 ON compta_ecriture (achat_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8852BE6C7970E16F ON compta_ecriture (reglement_client_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8852BE6C385073AF ON compta_ecriture (regelement_fournisseur_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8852BE6C41D81563 ON compta_ecriture (depense_id)');
        $this->addSql('ALTER TABLE customer_commande ADD tva INT NOT NULL, ADD montant_ttc INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C7DC7170A');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6CFE95D117');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C7970E16F');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C385073AF');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C41D81563');
        $this->addSql('DROP INDEX UNIQ_8852BE6C7DC7170A ON compta_ecriture');
        $this->addSql('DROP INDEX UNIQ_8852BE6CFE95D117 ON compta_ecriture');
        $this->addSql('DROP INDEX UNIQ_8852BE6C7970E16F ON compta_ecriture');
        $this->addSql('DROP INDEX UNIQ_8852BE6C385073AF ON compta_ecriture');
        $this->addSql('DROP INDEX UNIQ_8852BE6C41D81563 ON compta_ecriture');
        $this->addSql('ALTER TABLE compta_ecriture DROP vente_id, DROP achat_id, DROP reglement_client_id, DROP regelement_fournisseur_id, DROP depense_id');
        $this->addSql('ALTER TABLE customer_commande DROP tva, DROP montant_ttc');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
