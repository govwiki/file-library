<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180404133547 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE documents');
        $this->addSql('ALTER TABLE files ADD public_path VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL, ADD ext VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059727ACA70 FOREIGN KEY (parent_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE files RENAME INDEX idx_71c194bc727aca70 TO IDX_6354059727ACA70');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE documents (id BIGINT AUTO_INCREMENT NOT NULL, uploaded_by_id VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, path LONGTEXT NOT NULL COLLATE utf8_unicode_ci, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, slug VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, type VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, type_slug VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, state VARCHAR(2) NOT NULL COLLATE utf8_unicode_ci, year INT NOT NULL, file_size INT NOT NULL, uploaded_at DATETIME NOT NULL, INDEX IDX_A2B07288A2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (username)');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059727ACA70');
        $this->addSql('ALTER TABLE files DROP public_path, DROP created_at, DROP ext');
        $this->addSql('ALTER TABLE files RENAME INDEX idx_6354059727aca70 TO IDX_71C194BC727ACA70');
    }
}
