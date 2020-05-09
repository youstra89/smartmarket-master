<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508113228 extends AbstractMigration
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
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C444E82EE');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6CCE062FF9');
        $this->addSql('ALTER TABLE compta_ecriture ADD is_editable TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C444E82EE FOREIGN KEY (debit_id) REFERENCES compta_compte_exercice (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CCE062FF9 FOREIGN KEY (credit_id) REFERENCES compta_compte_exercice (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6C444E82EE');
        $this->addSql('ALTER TABLE compta_ecriture DROP FOREIGN KEY FK_8852BE6CCE062FF9');
        $this->addSql('ALTER TABLE compta_ecriture DROP is_editable');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6C444E82EE FOREIGN KEY (debit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE compta_ecriture ADD CONSTRAINT FK_8852BE6CCE062FF9 FOREIGN KEY (credit_id) REFERENCES compta_compte (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
