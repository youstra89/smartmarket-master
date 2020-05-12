<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200510181154 extends AbstractMigration
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
        $this->addSql('ALTER TABLE compta_ecriture DROP INDEX UNIQ_8852BE6CFE95D117, ADD INDEX IDX_8852BE6CFE95D117 (achat_id)');
        $this->addSql('ALTER TABLE compta_ecriture DROP INDEX UNIQ_8852BE6C7DC7170A, ADD INDEX IDX_8852BE6C7DC7170A (vente_id)');
        $this->addSql('ALTER TABLE provider_commande ADD exercice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE provider_commande ADD CONSTRAINT FK_26A8C89489D40298 FOREIGN KEY (exercice_id) REFERENCES compta_exercice (id)');
        $this->addSql('CREATE INDEX IDX_26A8C89489D40298 ON provider_commande (exercice_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_ecriture DROP INDEX IDX_8852BE6C7DC7170A, ADD UNIQUE INDEX UNIQ_8852BE6C7DC7170A (vente_id)');
        $this->addSql('ALTER TABLE compta_ecriture DROP INDEX IDX_8852BE6CFE95D117, ADD UNIQUE INDEX UNIQ_8852BE6CFE95D117 (achat_id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider_commande DROP FOREIGN KEY FK_26A8C89489D40298');
        $this->addSql('DROP INDEX IDX_26A8C89489D40298 ON provider_commande');
        $this->addSql('ALTER TABLE provider_commande DROP exercice_id');
    }
}
