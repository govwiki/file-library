<?php

namespace App\Command;

use App\Entity\Directory;
use App\Entity\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityFactory
     */
    private $factory;

    /**
     * @var integer
     */
    private $bucketCount = 0;

    /**
     * IndexCommand constructor.
     *
     * @param EntityManagerInterface $em      A EntityManagerInterface
     *                                        instance.
     * @param EntityFactory          $factory A EntityFactory instance.
     */
    public function __construct(
        EntityManagerInterface $em,
        EntityFactory $factory
    ) {
        parent::__construct(self::NAME);

        $this->em = $em;
        $this->factory = $factory;
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

        $path = rtrim($absPath, '/');

        $output->writeln(sprintf(
            '<info>Index documents in "%s" directory:</info>',
            $path
        ));

        $this->indexDocument($path, $output);

        return 0;
    }

    /**
     * @param string          $path     A path to indexed directory.
     * @param OutputInterface $output   A OutputInterface instance.
     * @param Directory|null  $previous A previous directory.
     *
     * @return void
     */
    private function indexDocument(
        string $path,
        OutputInterface $output,
        Directory $previous = null
    ) {
        $output->writeln(sprintf('<comment>Process "%s" directory</comment>', $path));

        $iterator = new \DirectoryIterator($path);
        $iterator = new \CallbackFilterIterator($iterator, function (\DirectoryIterator $file) {
            return !$file->isDot();
        });

        /** @var \DirectoryIterator $file */
        foreach ($iterator as $file) {
            $name = $file->getFilename();

            if ($file->isDir()) {
                $directory = $this->factory->createDirectory($name, $previous);
                $this->persist($directory);

                $this->indexDocument($path .'/'. $name, $output, $directory);
            } else {
                $this->persist($this->factory->createDocument(
                    $name,
                    $file->getSize(),
                    $previous
                ));
            }
        }
    }

    /**
     * @param object $entity A persisted entity.
     *
     * @return void
     */
    private function persist($entity)
    {
        $this->em->persist($entity);
        if (++$this->bucketCount === self::BUCKET_SIZE) {
            $this->em->flush();
            $this->bucketCount = 0;
        }
    }
}
