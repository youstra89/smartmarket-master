<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190131164325 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, total_amount INT DEFAULT NULL, ended TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provider_commande (id INT AUTO_INCREMENT NOT NULL, commande_id INT DEFAULT NULL, provider_id INT NOT NULL, reference VARCHAR(255) NOT NULL, additional_fees INT NOT NULL, transport INT NOT NULL, dedouanement INT NOT NULL, currency_cost INT NOT NULL, forwarding_cost INT NOT NULL, UNIQUE INDEX UNIQ_26A8C89482EA2E54 (commande_id), INDEX IDX_26A8C894A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE provider_commande ADD CONSTRAINT FK_26A8C89482EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE provider_commande ADD CONSTRAINT FK_26A8C894A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE provider_commande DROP FOREIGN KEY FK_26A8C89482EA2E54');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE provider_commande');
    }
}
