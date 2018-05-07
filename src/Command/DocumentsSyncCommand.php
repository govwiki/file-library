<?php

namespace App\Command;

use App\Service\Storage\Physical\AzurePhysicalStorage;
use Slim\Http\Stream;
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
     * @var AzurePhysicalStorage
     */
    private $azureStorage;

    /**
     * SyncDocumentsCommand constructor.
     *
     * @param AzurePhysicalStorage $azureStorage A AzurePhysicalStorage instance.
     */
    public function __construct(AzurePhysicalStorage $azureStorage)
    {
        parent::__construct(self::NAME);

        $this->azureStorage = $azureStorage;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Move documents from ftp to azure storage.')
            ->addArgument('host', InputArgument::REQUIRED, 'FTP host from which we get documents')
            ->addArgument('directories', InputArgument::REQUIRED, 'Comma separated list of directories names which is used for sync')
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Concurrency connection count', 4)
            ->addOption('port', 'x', InputOption::VALUE_REQUIRED, 'FTP server port', 21)
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
        $concurrency = (int) $input->getOption('concurrency');
        $transform = $input->hasOption('transform');

        $host = $input->getArgument('host');
        $port = (int) $input->getOption('port');
        $requestedDirectories = \array_map('trim', \explode(',', $input->getArgument('directories')));

        $user = $input->getOption('user');
        $password = $input->getOption('password');

        $output->write(\sprintf(
            '<info>Start fetching documents from %s:%s to azure file storage </info>',
            $host,
            $port
        ));
        if ($transform) {
            $output->writeln('with transformation');
        } else {
            $output->writeln('without transformation');
        }

        //
        // Start child process.
        //
        $childs = [];

        $output->writeln(\sprintf('Spawn %d childs', $concurrency));

        for ($i = 0; $i < $concurrency; ++$i) {
            $pid = \pcntl_fork();
            switch ($pid) {
                case -1:
                    $output->writeln('Can\'t for new child');
                    return 1;

                case 0:
                    $output->writeln(\sprintf('<info>[CHILD %d] Ready</info>', \posix_getpid()));

                    try {
                        $connection = new FTPConnection($host, $user, $password, $port);
                    } catch (\LogicException $exception) {
                        $output->writeln(\sprintf(
                            '<error>[CHILD %s] Can\'t connect to FTP server: %s</error>',
                            \posix_getpid(),
                            $exception->getMessage()
                        ));

                        return 1;
                    }

                    $queue = \msg_get_queue(self::QUEUE_KEY);

                    if ($transform) {
                        $this->childProcessWithTransformation($connection, $output, $queue);
                    } else {
                        $this->childProcessWithoutTransformation($connection, $output, $queue);
                    }

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

        try {
            $connection = new FTPConnection($host, $user, $password, $port);
        } catch (\LogicException $exception) {
            $output->writeln(\sprintf(
                '<error>[MAIN] Can\'t connect to FTP server: %s</error>',
                $exception->getMessage()
            ));

            return 1;
        }

        $this->masterProcess($output, $connection, $requestedDirectories);

        $output->writeln('<info>[MAIN] Kill child process</info>');
        foreach ($childs as $child) {
            \posix_kill($child, \SIGKILL);
        }

        $status = null;
        \pcntl_wait($status);

        return 0;
    }

    /**
     * @param FTPConnection   $connection A FTPConnection instance.
     * @param OutputInterface $output     A OutputInterface instance.
     * @param resource        $queue      A opened queue resource.
     *
     * @return void
     */
    public function childProcessWithoutTransformation(
        FTPConnection $connection,
        OutputInterface $output,
        $queue
    ) {
        $errors = [];
        $msgtype = null;
        $data = null;
        $err = null;
        $pid = \posix_getpid();

        while (true) {
            try {
                \msg_receive($queue, 12, $msgtype, 10000, $data, true, 0, $err);

                list ($directory, $document) = $data;
                if ($document[0] === '.') {
                    //
                    // Don't copy hidden files.
                    //
                    continue;
                }

                $path = $directory . '/' . $document;

                if ($this->azureStorage->isExists($path)) {
                    $output->writeln(
                        \sprintf(
                            '[CHILD %s] File "%s" is already exists, skip',
                            $pid,
                            $path
                        )
                    );

                    continue;
                }

                $output->writeln(
                    \sprintf(
                        '[CHILD %s] Start moving file "%s" to "%s"',
                        $pid,
                        $path,
                        $path
                    )
                );

                $file = $connection->getFile($path);
                $this->azureStorage->store(new Stream($file), $path);
            } catch (\Throwable $exception) {
                $output->writeln(\sprintf(
                    '<error>Got exception while processing file: [%s:%s] %s</error>',
                    $exception->getFile(),
                    $exception->getFile(),
                    $exception->getMessage()
                ));
                $errors[] = \sprintf(
                    'Can\'t process file "%s", %s %s',
                    $path,
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
     * @param FTPConnection   $connection A FTPConnection instance.
     * @param OutputInterface $output     A OutputInterface instance.
     * @param resource        $queue      A opened queue resource.
     *
     * @return void
     */
    public function childProcessWithTransformation(
        FTPConnection $connection,
        OutputInterface $output,
        $queue
    ) {
        $errors = [];
        $msgtype = null;
        $data = null;
        $err = null;
        $pid = \posix_getpid();

        while (true) {
            try {
                \msg_receive($queue, 12, $msgtype, 10000, $data, true, 0, $err);

                list ($directory, $document) = $data;
                $srcPath = $directory . '/' . $document;

                $matches = [];
                if ((preg_match(self::FILENAME_PATTERN, $document, $matches) !== 1) || ! isset($matches['year'])) {
                    $output->writeln(
                        \sprintf(
                            '<error>[CHILD %s] Can\'t determine destination path for "%s"</error>',
                            $pid,
                            $srcPath
                        )
                    );
                }

                $destPath = $directory . '/' . $matches['year'] . '/' . $document;

                if ($this->azureStorage->isExists($destPath)) {
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

                $file = $connection->getFile($srcPath);
                $this->azureStorage->store(new Stream($file), $destPath);
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
     * @param OutputInterface $output               A OutputInterface instance.
     * @param FTPConnection   $connection           A FTPConnection instance.
     * @param string[]        $requestedDirectories Array of requested directories
     *                                              names.
     *
     * @return void
     */
    public function masterProcess(
        OutputInterface $output,
        FTPConnection $connection,
        array $requestedDirectories
    ) {
        $existsDirectories = $connection->listFiles('.');
        if (\is_bool($existsDirectories)) {
            $output->writeln('<error>[MAIN] Can\'t get list of available directories</error>');

            return;
        }

        $processedDirectories = \array_intersect($requestedDirectories, $existsDirectories);
        $queue = \msg_get_queue(self::QUEUE_KEY);

        foreach ($processedDirectories as $directory) {
            $documents = $connection->listFiles('./'. $directory);
            $output->writeln(\sprintf(
                '[MAIN] Process directory %s (~ %d files)',
                $directory,
                count($documents)
            ));

            foreach ($documents as $document) {
                if (\preg_match(self::DOCUMENT_PATTERN, $document)) {
                    \msg_send($queue, 12, [ $directory, $document ]);
                }
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
