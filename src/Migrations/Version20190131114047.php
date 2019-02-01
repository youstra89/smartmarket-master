<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190131114047 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, total_amount INT DEFAULT NULL, ended TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provider_order (id INT AUTO_INCREMENT NOT NULL, ordeer_id INT NOT NULL, provider_id INT NOT NULL, reference VARCHAR(255) NOT NULL, additional_fees INT NOT NULL, UNIQUE INDEX UNIQ_AC7F26ECA582B621 (ordeer_id), INDEX IDX_AC7F26ECA53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE provider_order ADD CONSTRAINT FK_AC7F26ECA582B621 FOREIGN KEY (ordeer_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE provider_order ADD CONSTRAINT FK_AC7F26ECA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE provider_order DROP FOREIGN KEY FK_AC7F26ECA582B621');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE provider_order');
    }
}
