<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191222185810 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE provider ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE provider ADD CONSTRAINT FK_92C4739CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE provider ADD CONSTRAINT FK_92C4739C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_92C4739CB03A8386 ON provider (created_by_id)');
        $this->addSql('CREATE INDEX IDX_92C4739C896DBBDE ON provider (updated_by_id)');
        $this->addSql('ALTER TABLE product ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, CHANGE stock stock integer unsigned');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADB03A8386 ON product (created_by_id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD896DBBDE ON product (updated_by_id)');
        $this->addSql('ALTER TABLE customer ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_81398E09B03A8386 ON customer (created_by_id)');
        $this->addSql('CREATE INDEX IDX_81398E09896DBBDE ON customer (updated_by_id)');
        $this->addSql('ALTER TABLE `order` ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F5299398B03A8386 ON `order` (created_by_id)');
        $this->addSql('CREATE INDEX IDX_F5299398896DBBDE ON `order` (updated_by_id)');
        $this->addSql('ALTER TABLE settlement ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B51B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B51896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_DD9F1B51B03A8386 ON settlement (created_by_id)');
        $this->addSql('CREATE INDEX IDX_DD9F1B51896DBBDE ON settlement (updated_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09896DBBDE');
        $this->addSql('DROP INDEX IDX_81398E09B03A8386 ON customer');
        $this->addSql('DROP INDEX IDX_81398E09896DBBDE ON customer');
        $this->addSql('ALTER TABLE customer DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398896DBBDE');
        $this->addSql('DROP INDEX IDX_F5299398B03A8386 ON `order`');
        $this->addSql('DROP INDEX IDX_F5299398896DBBDE ON `order`');
        $this->addSql('ALTER TABLE `order` DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD896DBBDE');
        $this->addSql('DROP INDEX IDX_D34A04ADB03A8386 ON product');
        $this->addSql('DROP INDEX IDX_D34A04AD896DBBDE ON product');
        $this->addSql('ALTER TABLE product DROP created_by_id, DROP updated_by_id, CHANGE stock stock INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE provider DROP FOREIGN KEY FK_92C4739CB03A8386');
        $this->addSql('ALTER TABLE provider DROP FOREIGN KEY FK_92C4739C896DBBDE');
        $this->addSql('DROP INDEX IDX_92C4739CB03A8386 ON provider');
        $this->addSql('DROP INDEX IDX_92C4739C896DBBDE ON provider');
        $this->addSql('ALTER TABLE provider DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE settlement DROP FOREIGN KEY FK_DD9F1B51B03A8386');
        $this->addSql('ALTER TABLE settlement DROP FOREIGN KEY FK_DD9F1B51896DBBDE');
        $this->addSql('DROP INDEX IDX_DD9F1B51B03A8386 ON settlement');
        $this->addSql('DROP INDEX IDX_DD9F1B51896DBBDE ON settlement');
        $this->addSql('ALTER TABLE settlement DROP created_by_id, DROP updated_by_id');
    }
}
