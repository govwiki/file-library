<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180613080333 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX IDX_SLUG ON files (slug)');
        $this->addSql('CREATE INDEX IDX_PUBLIC_PATH ON files (public_path)');
        $this->addSql('ALTER TABLE files RENAME INDEX idx_ad63a3de727aca70 TO IDX_6354059727ACA70');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_SLUG ON files');
        $this->addSql('DROP INDEX IDX_PUBLIC_PATH ON files');
        $this->addSql('ALTER TABLE files RENAME INDEX idx_6354059727aca70 TO IDX_AD63A3DE727ACA70');
    }
}
