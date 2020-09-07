<?php
declare(strict_types = 1);

namespace App\Command;

use App\Entity\EntityFactory;
use App\Storage\Directory;
use App\Storage\Storage;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * Class DocumentUpdateFileCommand
 */
class DocumentUpdateFileCommand extends Command
{
    const NAME = 'document:index:update:file';
    const TEST_FILE_PATH = '/Community College District/2004/OR Blue Mountain Community College 2004_test.pdf';

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param Storage $storage
     * @param EntityFactory $entityFactory
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Storage $storage,
        EntityFactory $entityFactory,
        EntityManagerInterface $em
    ) {
        parent::__construct(static::NAME);

        $this->storage = $storage;
        $this->entityFactory = $entityFactory;
        $this->slugify = new Slugify();
        $this->em = $em;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Update one file by path');
            //->addArgument('path', InputArgument::REQUIRED, 'Path to file');
    }

    /**
     * Executes the current command.
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
        $symfonyStyle = new SymfonyStyle($input, $output);

        $store   = new SemaphoreStore();
        $factory = new Factory($store);
        $lock = $factory->createLock($this->getName());

        if (! $lock->acquire()) {
            $symfonyStyle->error('Command already run.');
            return 0;
        }

        $helper           = $this->getHelper('question');
        $questionPath = new Question('Enter a path: ');

        $questionPath->setNormalizer(function ($value) {
            return trim($value);
        });

        $questionPath->setValidator(function ($answer) use ($symfonyStyle){
            if (! is_string($answer) || empty($answer)) {
                $symfonyStyle->error('The path cannot be empty');
            }

            return $answer;
        });

        $path = $helper->ask($input, $output, $questionPath);

        $isFileExist = $this->storage->isFileExistInStorage($path);

        if (! $isFileExist) {
            $symfonyStyle->error(sprintf(
                "File not found in Azure Storage by path: %s",
                $path
            ));

            return 0;
        }

        $directory = new Directory($this->storage->getAdapter(), $this->storage->getIndex(), $path);

        $publicPathDirectory = stristr($directory->getPath(), $directory->getName(), true);

        if (false === $publicPathDirectory) {
            $symfonyStyle->error("Failed to calculate parent category");
            return 0;
        }

        $parentDirectory = $this->getParentDirectoryBySlug($publicPathDirectory);

        if (null === $parentDirectory) {
            $symfonyStyle->error(sprintf(
                "Parent directory not found by public path: %s",
                $publicPathDirectory
            ));
            return 0;
        }

        $file = $this->entityFactory->createDocument(
            $directory->getName(),
            0,
            $parentDirectory
        );


        $this->em->persist($file);
        $this->em->flush();

        $symfonyStyle->success('File has been updated.');
        return 0;
    }

    /**
     * @param string $publicPathDirectory
     * @return \App\Entity\Directory|null
     */
    private function getParentDirectoryBySlug(string $publicPathDirectory)
    {
        return $this->storage->getIndex()->getDirectoryBySlug(
            $this->slugify->slugify($publicPathDirectory)
        );
    }
}
