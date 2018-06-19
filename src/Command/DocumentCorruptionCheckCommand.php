<?php

namespace App\Command;

use App\Entity\AbstractFile;
use App\Storage\Storage;
use Doctrine\DBAL\Connection;
use MKraemer\ReactPCNTL\PCNTL;
use React\EventLoop\Factory;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class DocumentCorruptionCheckCommand
 *
 * @package App\Command
 */
class DocumentCorruptionCheckCommand extends AbstractParallelCommand
{

    const LIMIT = 100;
    const QUEUE_KEY = 1113;
    const NAME = 'document:corruption:check';

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $pdfInfoBinary;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * DocumentsCorruptionCheckCommand constructor.
     *
     * @param Storage    $storage       A Storage instance.
     * @param Connection $connection    A DBAL Connection instance.
     * @param string     $pdfInfoBinary Path to 'pdfinfo' binary.
     */
    public function __construct(
        Storage $storage,
        Connection $connection,
        string $pdfInfoBinary
    ) {
        parent::__construct(self::NAME);

        $this->storage = $storage;
        $this->connection = $connection;
        $this->pdfInfoBinary = $pdfInfoBinary;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Checks and fix corrupted pdf document in storage')
            ->addArgument('url', InputArgument::REQUIRED, 'WebDav connection url from which we get documents')
            ->addArgument('basePath', InputArgument::REQUIRED, 'Path to directory wth documents on WebDav server')
            ->addOption('directory', 'd', InputOption::VALUE_REQUIRED, 'Path to checked directory', '/')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Username which is used for authentication')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Password which is used for authentication');
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
        $this->directory = $input->getOption('directory');

        $this->url = $input->getArgument('url');
        $this->basePath = $input->getArgument('basePath');
        $this->user = $input->getOption('user');
        $this->password = $input->getOption('password');
    }


    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    protected function childProcess($queue, OutputInterface $output)
    {
        $this->connection->close();
        $this->connection->connect();

        $output->writeln('Started!');

        $stop = false;

        $loop = Factory::create();
        $pcntl = new PCNTL($loop);

        $pcntl->on(\SIGTERM, function () use (&$stop, $output) {
            $output->writeln('Got SIGTERM signal');
            $stop = true;
        });

        $pcntl->on(\SIGINT, function () use (&$stop, $output) {
            $output->writeln('Got SIGINT signal');
            $stop = true;
        });

        $webDav = new WebDavConnection(
            $this->url,
            $this->basePath,
            $this->user,
            $this->password
        );

        $loop->addPeriodicTimer(0.1, function (TimerInterface $timer) use (&$stop, $output, $queue, $webDav) {
            if ($stop) {
                $output->writeln('Begin graceful stop');
                $timer->cancel();
                $timer->getLoop()->stop();
                return;
            }

            $msgtype = null;
            $err = null;
            $path = null;

            try {
                \msg_receive($queue, 12, $msgtype, 100000000, $path, true, MSG_IPC_NOWAIT, $err);

                if ($path !== false) {
                    $output->writeln(\sprintf('Check document "%s"', $path));
                    $file = $this->storage->getFile($path);

                    if ($file === null) {
                        throw new \RuntimeException(\sprintf(
                            'File "%s" not exists.',
                            $file
                        ));
                    }

                    // Check file content.
                    $tmpPath = \tempnam(\sys_get_temp_dir(), 'corr_check_');
                    if (($tmpPath === false) || ! \is_writable($tmpPath)) {
                        throw new \RuntimeException('Can\'t download file for checking \'cause tmp dir is not writable');
                    }

                    \file_put_contents($tmpPath, $file->getContent()->getContents());
                    $process = new Process(\sprintf(
                        '%s %s 2>1 > /dev/null',
                        $this->pdfInfoBinary,
                        $tmpPath
                    ));

                    $corrupted = $process->run() !== 0;
                    \unlink($tmpPath);

                    if ($corrupted) {
                        // Download file from old storage again.
                        $webDavPath = \preg_replace('|/\d{4}/|', '/', $path);
                        $output->writeln(\sprintf(
                            '<error>File "%s" corrupted. Download version from WebDav "%s"</error>',
                            $path,
                            $webDavPath
                        ));

                        $stream = $webDav->getFile($webDavPath);
                        if ($stream === null) {
                            throw new \RuntimeException(\sprintf(
                                'File "%s/%s/%s" not exists',
                                $this->url,
                                $this->basePath,
                                $webDavPath
                            ));
                        }

                        $this->storage->getAdapter()->createFile($path, $stream);
                    } else {
                        $output->writeln(\sprintf('<info>File "%s" valid.</info>', $path));
                    }
                }

                // Sleep in order to prevent request limit exceed.
                \usleep(\random_int(5, 9) * 100000);
            } catch (\Throwable $exception) {
                $output->writeln(\sprintf(
                    '<error>Got exception while processing file: [%s:%s] %s</error>',
                    $exception->getFile(),
                    $exception->getFile(),
                    $exception->getMessage()
                ));
            }
        });

        $loop->run();

        $output->writeln('Stopped!');
        exit(0);
    }

    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    protected function mainProcess($queue, OutputInterface $output)
    {
        $output->writeln('Start corruption check');
        $this->connection->close();
        $this->connection->connect();

        $offset = 0;

        $listBuilder = $this->storage->getIndex()
            ->createFileListBuilder($this->directory === '/' ? null : $this->directory)
            ->recursive(true)
            ->showHidden(true)
            ->setLimit(self::LIMIT);

        do {
            $output->writeln(\sprintf(
                'Fetch %d document with offset %d',
                self::LIMIT,
                $offset
            ));
            /** @var AbstractFile[] $documents */
            $documents = \iterator_to_array($listBuilder->setOffset($offset));

            foreach ($documents as $document) {
                if ($document->isDocument()) {
                    \msg_send($queue, 12, $document->getPublicPath());
                }
            }

            $offset += self::LIMIT;
            \usleep(800000);
        } while (\count($documents) === self::LIMIT);

    }
}
