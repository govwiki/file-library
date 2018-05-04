<?php

namespace App\Command;

use App\Service\FileStorage\FileList\FileInterface;
use App\Service\FileStorage\FileStorageInterface;
use App\Service\FileStorage\Index\FileStorageIndexInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class IndexCommand
 *
 * @package App\Command
 */
class IndexCommand extends Command
{

    const BUCKET_SIZE = 25;
    const NAME = 'document:index';

    const DOCUMENT_PATTERN = '/([A-Z]{2})\s+(.*?)\s+(\d{4})\.pdf/i';

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * @var FileStorageIndexInterface
     */
    private $fileStorageIndex;

    /**
     * @var integer
     */
    private $bucketCount = 0;

    /**
     * IndexCommand constructor.
     *
     * @param FileStorageIndexInterface $fileStorageIndex A FileStorageIndexInterface
     *                                                    instance.
     * @param FileStorageInterface      $fileStorage      A FileStorageInterface
     *                                                    instance.
     */
    public function __construct(
        FileStorageIndexInterface $fileStorageIndex,
        FileStorageInterface $fileStorage
    ) {
        parent::__construct(self::NAME);

        $this->fileStorageIndex = $fileStorageIndex;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Recursively index documents in root path.');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return integer
     *
     * @see setCode()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('> Clear file index: ');
        $this->fileStorageIndex->clear();
        $output->writeln('[ <info>OK</info> ]');

        $output->writeln('> Index documents in file storage root directory: ');
        $this->indexDocument('/', $output);
        $output->writeln('> Index documents in file storage root directory: [ <info>OK</info> ]');

        return 0;
    }

    /**
     * @param string          $path   A path to indexed directory.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    private function indexDocument(
        string $path,
        OutputInterface $output
    ) {
        $output->writeln(sprintf("\t<comment>Process \"%s\" directory</comment>", $path));
        $iterator = $this->fileStorage->listFiles($path);

        /** @var FileInterface $file */
        foreach ($iterator as $file) {
            $name = $file->getName();
            $filePath = rtrim($path, '/') .'/'. $name;

            if ($file->isDirectory()) {
                $this->fileStorageIndex->index($filePath)->flush();
                $this->indexDocument($filePath, $output);
            } else {
                $this->fileStorageIndex->index($filePath, false, $file->getSize());
            }

            if (++$this->bucketCount === self::BUCKET_SIZE) {
                $this->fileStorageIndex->flush();
                $this->bucketCount = 0;
            }
        }

        $this->fileStorageIndex->flush();
    }
}
