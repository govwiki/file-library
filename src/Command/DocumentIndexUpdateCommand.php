<?php

namespace App\Command;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use App\Entity\Document;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DocumentIndexUpdateCommand
 *
 * @package App\Command
 */
class DocumentIndexUpdateCommand extends DocumentIndexInitializeCommand
{
    const QUEUE_KEY         = DocumentIndexInitializeCommand::QUEUE_KEY + 1;
    const TEMP_TABLE_PREFIX = '__temp_';

    const NAME = 'document:index:update';

    /**
     * @var string
     */
    private $originalTableName;

    /**
     * @var string
     */
    private $tmpTableName;

    /**
     * @var string
     */
    private $fkConstraintSql;

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Update document index.');
    }

    /**
     * @param InputInterface  $input  A InputInterface instance.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doInitialize(InputInterface $input, OutputInterface $output)
    {
        //
        // Create temporary table for new index.
        //
        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getClassMetadata(AbstractFile::class);
        $this->originalTableName = $metadata->getTableName();
        $this->tmpTableName = '__temp_'. $this->originalTableName;

        $output->writeln(\sprintf('Create temporary table %s', $this->tmpTableName));
        $this->em->getConnection()->executeQuery('DROP TABLE IF EXISTS '. $this->tmpTableName);

        // Get original FK constrain sql definition.
        $this->fkConstraintSql = $tool->getCreateSchemaSql([ $metadata ])[1];

        $metadata->setPrimaryTable([ 'name' => $this->tmpTableName ]);
        $tool->createSchema([ $metadata ]);

        $this->em->getClassMetadata(Document::class)->setPrimaryTable([ 'name' => $this->tmpTableName ]);
        $this->em->getClassMetadata(Directory::class)->setPrimaryTable([ 'name' => $this->tmpTableName ]);
    }

    /**
     * @param InputInterface  $input  A InputInterface instance.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function doFinalize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Replace index');

        $bckName = $this->originalTableName . '_bck';
        $conn = $this->em->getConnection();
        $conn->executeQuery(\sprintf(
            'RENAME TABLE %s TO %s',
            $this->originalTableName,
            $bckName
        ));
        $conn->executeQuery(\sprintf(
            'RENAME TABLE %s TO %s',
            $this->tmpTableName,
            $this->originalTableName
        ));
        $conn->executeQuery('DROP TABLE '. $bckName);
        $conn->executeQuery($this->fkConstraintSql);
    }
}
