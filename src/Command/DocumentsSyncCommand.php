<?php

namespace App\Command;

use App\Storage\Storage;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DocumentsSyncCommand
 *
 * @package App\Command
 */
class DocumentsSyncCommand extends Command
{

    const QUEUE_KEY = 1111;
    const NAME = 'document:sync';

    const DOCUMENT_PATTERN = '/([A-Z]{2})\s+(.*?)\s+(\d{4})\.pdf/i';
    const FILENAME_PATTERN = '/(?P<year>\d{4})\.\w+$/';

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * SyncDocumentsCommand constructor.
     *
     * @param Storage    $storage    A Storage instance.
     * @param Connection $connection A DBAL Connection instance.
     */
    public function __construct(Storage $storage, Connection $connection)
    {
        parent::__construct(self::NAME);

        $this->storage = $storage;
        $this->connection = $connection;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Move documents from WebDav to azure storage.')
            ->addArgument('url', InputArgument::REQUIRED, 'WebDav connection url from which we get documents')
            ->addArgument('directory', InputArgument::REQUIRED, 'Path to synced directory')
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Concurrency connection count', 4)
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Username which is used for authentication')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Password which is used for authentication')
            ->addOption('transform', 't', InputOption::VALUE_NONE, 'Transform documents or not');
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
        //
        // Because we get a very huge list response for some directories and we
        // may exceed allowed memory limit very fast.
        //
        \ini_set('memory_limit', '1G');

        $concurrency = (int) $input->getOption('concurrency');
        $transform = $input->getOption('transform');

        $url = $input->getArgument('url');
        $directory = $input->getArgument('directory');

        $user = $input->getOption('user');
        $password = $input->getOption('password');

        $output->write(\sprintf(
            '<info>Start fetching documents from "%s" to azure file storage </info>',
            \rtrim($url, '/') . '/' . \ltrim($directory, '/')
        ));
        if ($transform) {
            $output->writeln('<info><options=bold>with</> transformation</info>');
        } else {
            $output->writeln('<info><options=bold>without</> transformation</info>');
        }

        //
        // Start child process.
        //
        $childs = [];

        $output->writeln(\sprintf('Spawn %d childs', $concurrency));

        //
        // We assume that directory in WebDav as equal to Azure File Storage.
        //
        $destDir = \basename($directory);

        for ($i = 0; $i < $concurrency; ++$i) {
            $pid = \pcntl_fork();
            switch ($pid) {
                case -1:
                    $output->writeln('Can\'t for new child');
                    return 1;

                case 0:
                    $output->writeln(\sprintf('<info>[CHILD %d] Ready</info>', \posix_getpid()));

                    $conn = new WebDavConnection(
                        $url,
                        $directory,
                        $user,
                        $password
                    );

                    //
                    // Reconnect to database in each child.
                    //
                    $this->connection->close();
                    $this->connection->connect();

                    $this->childProcess($conn, $output, $this->createTransformer($destDir, $transform));

                    break;

                default:
                    $childs[] = $pid;
            }
        }

        //
        // Main process.
        //
        // Fetch list of directories, then get list of all files in them and send
        // it into queue.
        //

        $conn = new WebDavConnection(
            $url,
            $directory,
            $user,
            $password
        );

        $this->masterProcess($conn, ! $transform);

        $output->writeln('<info>[MAIN] Kill child process</info>');
        foreach ($childs as $child) {
            \posix_kill($child, \SIGKILL);
        }

        $status = null;
        \pcntl_wait($status);

        return 0;
    }

    /**
     * @param string  $destDir   Destination dir name.
     * @param boolean $transform Should path make additional transformation.
     *
     * @return \Closure
     */
    private function createTransformer(string $destDir, bool $transform): \Closure
    {
        $transformer = function ($documentName) use ($destDir) {
            return '/'. \trim($destDir, '/') .'/'. $documentName;
        };

        if ($transform) {
            $transformer = function ($documentName) use ($destDir) {
                $matches = [];
                if ((\preg_match(self::FILENAME_PATTERN, $documentName, $matches) !== 1) || ! isset($matches['year'])) {
                    throw new \DomainException('Can\'t determine destination path');
                }

                return '/'. \trim($destDir, '/') .'/'. $matches['year'] . '/' . $documentName;
            };
        }

        return $transformer;
    }

    /**
     * @param WebDavConnection $connection      A WebDavConnection instance.
     * @param OutputInterface  $output          A OutputInterface instance.
     * @param callable         $pathTransformer Path transformer.
     *
     * @return void
     */
    private function childProcess(
        WebDavConnection $connection,
        OutputInterface $output,
        callable $pathTransformer
    ) {
        $queue = \msg_get_queue(self::QUEUE_KEY);
        $errors = [];
        $msgtype = null;
        $srcPath = null;
        $err = null;
        $pid = \posix_getpid();

        while (true) {
            try {
                \msg_receive($queue, 12, $msgtype, 10000, $srcPath, true, 0, $err);

                try {
                    $destPath = $pathTransformer($srcPath);
                } catch (\DomainException $exception) {
                    $output->writeln(
                        \sprintf(
                            '<error>[CHILD %s] Error due processing "%s": %s</error>',
                            $pid,
                            $srcPath,
                            $exception->getMessage()
                        )
                    );

                    continue;
                }

                if ($this->storage->isFileExists($destPath)) {
                    $output->writeln(
                        \sprintf(
                            '[CHILD %s] File "%s" is already exists, skip',
                            $pid,
                            $destPath
                        )
                    );

                    continue;
                }

                $output->writeln(
                    \sprintf(
                        '[CHILD %s] Start moving file "%s" to "%s"',
                        $pid,
                        $srcPath,
                        $destPath
                    )
                );

                $this->storage->createFile($destPath, $connection->getFile($srcPath));
            } catch (\Throwable $exception) {
                $output->writeln(\sprintf(
                    '<error>Got exception while processing file: [%s:%s] %s</error>',
                    $exception->getFile(),
                    $exception->getFile(),
                    $exception->getMessage()
                ));
                $errors[] = \sprintf(
                    'Can\'t process file "%s", %s %s',
                    $srcPath,
                    \get_class($exception),
                    $exception->getMessage()
                );
            }
        }

        foreach ($errors as $error) {
            $output->writeln(\sprintf(
                '<error>[CHILD %s] %s</error>',
                $pid,
                $error
            ));
        }
    }

    /**
     * @param WebDavConnection $connection A FTPConnection instance.
     * @param boolean          $processAll Process only matched files if false.
     *
     * @return void
     */
    public function masterProcess(
        WebDavConnection $connection,
        bool $processAll = false
    ) {
        $queue = \msg_get_queue(self::QUEUE_KEY);

        $documents = $connection->listFiles('/');

        foreach ($documents as $documentName) {
            $documentName = \ltrim($documentName, '/');
            if (($documentName !== '') && ($processAll || \preg_match(self::DOCUMENT_PATTERN, $documentName))) {
                \msg_send($queue, 12, $documentName);
            }
        }

        //
        // Wait until all messages from queue was consumed.
        //
        do {
            $count = \msg_stat_queue($queue)['msg_qnum'];
            \sleep(1);
        } while ($count > 0);

        \msg_remove_queue($queue);
    }
}
