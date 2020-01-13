<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200112225257 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE type_depense (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE depense (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, date_depense DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, amount INT NOT NULL, INDEX IDX_34059757C54C8C93 (type_id), INDEX IDX_34059757B03A8386 (created_by_id), INDEX IDX_34059757896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_34059757C54C8C93 FOREIGN KEY (type_id) REFERENCES type_depense (id)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_34059757B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_34059757896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product CHANGE stock stock integer unsigned');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY FK_34059757C54C8C93');
        $this->addSql('DROP TABLE type_depense');
        $this->addSql('DROP TABLE depense');
        $this->addSql('ALTER TABLE product CHANGE stock stock INT UNSIGNED DEFAULT NULL');
    }
}
