<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180405082634 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE files (id BIGINT AUTO_INCREMENT NOT NULL, parent_id BIGINT DEFAULT NULL, name VARCHAR(255) NOT NULL, public_path VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, file_size INT DEFAULT NULL, created_at DATETIME NOT NULL, type VARCHAR(255) NOT NULL, ext VARCHAR(255) DEFAULT NULL, INDEX IDX_6354059727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059727ACA70 FOREIGN KEY (parent_id) REFERENCES files (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059727ACA70');
        $this->addSql('DROP TABLE files');
    }
}
