<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200127181446 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE provider ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE provider ADD CONSTRAINT FK_92C4739CC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_92C4739CC76F1F52 ON provider (deleted_by_id)');
        $this->addSql('ALTER TABLE category ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_64C19C1C76F1F52 ON category (deleted_by_id)');
        $this->addSql('ALTER TABLE customer ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_81398E09C76F1F52 ON customer (deleted_by_id)');
        $this->addSql('ALTER TABLE mark ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F271C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6674F271C76F1F52 ON mark (deleted_by_id)');
        $this->addSql('ALTER TABLE product ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL, CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADC76F1F52 ON product (deleted_by_id)');
        $this->addSql('ALTER TABLE type_depense ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1C24F8A2C76F1F52 ON type_depense (deleted_by_id)');
        $this->addSql('ALTER TABLE `order` ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F5299398C76F1F52 ON `order` (deleted_by_id)');
        $this->addSql('ALTER TABLE returned_product ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE returned_product ADD CONSTRAINT FK_AF6E52FEC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AF6E52FEC76F1F52 ON returned_product (deleted_by_id)');
        $this->addSql('ALTER TABLE commande ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DC76F1F52 ON commande (deleted_by_id)');
        $this->addSql('ALTER TABLE depense ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_34059757C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_34059757C76F1F52 ON depense (deleted_by_id)');
        $this->addSql('ALTER TABLE echeance ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE echeance ADD CONSTRAINT FK_40D9893BC76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_40D9893BC76F1F52 ON echeance (deleted_by_id)');
        $this->addSql('ALTER TABLE settlement ADD deleted_by_id INT DEFAULT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B51C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_DD9F1B51C76F1F52 ON settlement (deleted_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1C76F1F52');
        $this->addSql('DROP INDEX IDX_64C19C1C76F1F52 ON category');
        $this->addSql('ALTER TABLE category DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DC76F1F52');
        $this->addSql('DROP INDEX IDX_6EEAA67DC76F1F52 ON commande');
        $this->addSql('ALTER TABLE commande DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09C76F1F52');
        $this->addSql('DROP INDEX IDX_81398E09C76F1F52 ON customer');
        $this->addSql('ALTER TABLE customer DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY FK_34059757C76F1F52');
        $this->addSql('DROP INDEX IDX_34059757C76F1F52 ON depense');
        $this->addSql('ALTER TABLE depense DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE echeance DROP FOREIGN KEY FK_40D9893BC76F1F52');
        $this->addSql('DROP INDEX IDX_40D9893BC76F1F52 ON echeance');
        $this->addSql('ALTER TABLE echeance DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F271C76F1F52');
        $this->addSql('DROP INDEX IDX_6674F271C76F1F52 ON mark');
        $this->addSql('ALTER TABLE mark DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C76F1F52');
        $this->addSql('DROP INDEX IDX_F5299398C76F1F52 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADC76F1F52');
        $this->addSql('DROP INDEX IDX_D34A04ADC76F1F52 ON product');
        $this->addSql('ALTER TABLE product DROP deleted_by_id, DROP is_deleted, DROP deleted_at, CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider DROP FOREIGN KEY FK_92C4739CC76F1F52');
        $this->addSql('DROP INDEX IDX_92C4739CC76F1F52 ON provider');
        $this->addSql('ALTER TABLE provider DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE returned_product DROP FOREIGN KEY FK_AF6E52FEC76F1F52');
        $this->addSql('DROP INDEX IDX_AF6E52FEC76F1F52 ON returned_product');
        $this->addSql('ALTER TABLE returned_product DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE settlement DROP FOREIGN KEY FK_DD9F1B51C76F1F52');
        $this->addSql('DROP INDEX IDX_DD9F1B51C76F1F52 ON settlement');
        $this->addSql('ALTER TABLE settlement DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2C76F1F52');
        $this->addSql('DROP INDEX IDX_1C24F8A2C76F1F52 ON type_depense');
        $this->addSql('ALTER TABLE type_depense DROP deleted_by_id, DROP is_deleted, DROP deleted_at');
    }
}
