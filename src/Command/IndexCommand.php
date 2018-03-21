<?php

namespace App\Command;

use App\Model\Document;
use App\Repository\DocumentRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * IndexCommand constructor.
     *
     * @param DocumentRepositoryInterface $repository A
     *                                                DocumentRepositoryInterface
     *                                                instance.
     */
    public function __construct(DocumentRepositoryInterface $repository)
    {
        parent::__construct(self::NAME);

        $this->repository = $repository;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Recursively index documents in specified path.')
            ->addArgument('directory', InputArgument::REQUIRED, 'Path to directory with documents');
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $path */
        $path = $input->getArgument('directory');
        /** @var string|boolean $absPath */
        $absPath = realpath($path);

        if (! is_string($absPath) || ! is_readable($absPath)) {
            $output->writeln(sprintf(
                '<error>Directory "%s" is not exists or not readable.</error>',
                $path
            ));
            return 127;
        }

        $path = rtrim($absPath, DIRECTORY_SEPARATOR);

        $output->writeln(sprintf(
            '<info>Index documents in "%s" directory:</info>',
            $path
        ));

        $this->indexDocument($path, $output);

        return 0;
    }

    /**
     * @param string          $path   A path to indexed directory.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    private function indexDocument(string $path, OutputInterface $output)
    {
        $typeDirs = $this->createDirIterator($path);
        $bucket = new DocumentBucket($this->repository, self::BUCKET_SIZE);

        /** @var \SplFileInfo $typeDir */
        foreach ($typeDirs as $typeDir) {
            $type = $typeDir->getFilename();
            $output->writeln(sprintf('  Found type <comment>"%s"</comment>', $type));

            $stateDirs = $this->createDirIterator($typeDir->getPathname());
            /** @var \SplFileInfo $stateDir */
            foreach ($stateDirs as $stateDir) {
                $state = $stateDir->getFilename();
                $output->writeln(sprintf('    Found state <comment>"%s"</comment>', $state));

                $yearDirs = $this->createDirIterator($stateDir->getPathname());

                /** @var \SplFileInfo $yearDir */
                foreach ($yearDirs as $yearDir) {
                    $year = $yearDir->getFilename();
                    $output->writeln(sprintf('      Found year <comment>"%s"</comment>', $year));

                    $documents = new \DirectoryIterator($yearDir->getPathname());
                    $documents = new \RegexIterator($documents, self::DOCUMENT_PATTERN);

                    /** @var \SplFileInfo $documentFile */
                    foreach ($documents as $documentFile) {
                        $name = preg_replace('/\.pdf$/i', '', $documentFile->getFilename());
                        $path = $documentFile->getPathname();
                        $size = filesize($path);

                        if (! is_int($size)) {
                            throw new \RuntimeException(sprintf(
                                'Can\'t get size of file "%s"',
                                $path
                            ));
                        }

                        $output->writeln(sprintf('        Add document <comment>"%s"</comment>', $name));

                        $bucket->attach(new Document(
                            $name,
                            $type,
                            $state,
                            (int) $year,
                            $path,
                            $size
                        ));
                    }
                }
            }
        }

        $bucket->flush();
    }

    /**
     * @param string $path A path to iterated directory.
     *
     * @return \Iterator
     */
    private function createDirIterator(string $path): \Iterator
    {
        $iterator = new \DirectoryIterator($path);
        return new \CallbackFilterIterator($iterator, function (\DirectoryIterator $file) {
            return $file->isDir() && !$file->isDot();
        });
    }
}
