<?php

namespace App\Command;

use App\Storage\Adapter\AdapterFile;
use App\Storage\Adapter\StorageAdapterInterface;
use App\Storage\Index\StorageIndexInterface;
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

    const NAME = 'document:index';

    /**
     * @var StorageAdapterInterface
     */
    private $adapter;

    /**
     * @var StorageIndexInterface
     */
    private $index;

    /**
     * IndexCommand constructor.
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface
     *                                         instance.
     * @param StorageIndexInterface   $index   A StorageIndexInterface
     *                                         instance.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        StorageIndexInterface $index
    ) {
        parent::__construct(self::NAME);

        $this->adapter = $adapter;
        $this->index = $index;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Recursively (re)index documents in storage.');
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
        $output->write('> Clear and storage index: ');
        $this->index->clearIndex();
        $output->writeln('[ <info>OK</info> ]');

        $output->writeln('> Index files:');
        $output->writeln('');
        $this->indexDirectory($output, '/');
        $this->index->flush();

        return 0;
    }

    /**
     * @param OutputInterface $output An OutputInterface instance.
     * @param string          $path   Path to indexed file.
     *
     * @return void
     */
    protected function indexDirectory(OutputInterface $output, string $path)
    {
        $output->writeln(\sprintf('  Index directory "%s"', $path));
        $files = $this->adapter->listFiles($path);

        /** @var AdapterFile $file */
        foreach ($files as $file) {
            if ($file->isDirectory()) {
                $this->index->createDirectory($file->getPath());
                $this->indexDirectory($output, $file->getPath());
            } else {
                $output->writeln(\sprintf('    Index file "%s"', $file->getPath()));
                $this->index->createFile($file->getPath(), $file->getSize());
            }
        }
    }
}
