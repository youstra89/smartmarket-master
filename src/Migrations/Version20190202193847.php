<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190202193847 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer ADD type_id INT NOT NULL, ADD residence VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09C54C8C93 FOREIGN KEY (type_id) REFERENCES customer_type (id)');
        $this->addSql('CREATE INDEX IDX_81398E09C54C8C93 ON customer (type_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09C54C8C93');
        $this->addSql('DROP INDEX IDX_81398E09C54C8C93 ON customer');
        $this->addSql('ALTER TABLE customer DROP type_id, DROP residence');
    }
}
