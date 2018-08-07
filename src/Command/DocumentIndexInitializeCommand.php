<?php

namespace App\Command;

use App\Storage\Adapter\AdapterFile;
use App\Storage\Adapter\StorageAdapterInterface;
use App\Storage\Index\StorageIndexInterface;
use Doctrine\ORM\EntityManagerInterface;
use MKraemer\ReactPCNTL\PCNTL;
use React\EventLoop\Factory;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DocumentIndexInitializeCommand
 *
 * @package App\Command
 */
class DocumentIndexInitializeCommand extends AbstractParallelCommand
{

    const QUEUE_KEY = 11;

    const MSG_DIRECTORY = 12;
    const MSG_FILE = 13;

    const NAME = 'document:index:initialize';

    /**
     * @var StorageAdapterInterface
     */
    protected $adapter;

    /**
     * @var StorageIndexInterface
     */
    protected $index;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * IndexInitCommand constructor.
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface
     *                                         instance.
     * @param StorageIndexInterface   $index   A StorageIndexInterface
     *                                         instance.
     * @param EntityManagerInterface  $em      A EntityManagerInterface
     *                                         instance.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        StorageIndexInterface $index,
        EntityManagerInterface $em
    ) {
        parent::__construct(static::NAME);

        $this->adapter = $adapter;
        $this->index = $index;
        $this->em = $em;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Index documents in remote storage.');
    }

    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    protected function childProcess($queue, OutputInterface $output)
    {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

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

        $loop->addPeriodicTimer(0.1, function (TimerInterface $timer) use (&$stop, $output, $queue) {
            if ($stop) {
                $output->writeln('Begin graceful stop');
                $timer->cancel();
                $timer->getLoop()->stop();
                return;
            }

            $msgtype = null;
            $err = null;
            $data = null;

            try {
                \msg_receive($queue, 0, $msgtype, 100000000, $data, true, MSG_IPC_NOWAIT, $err);

                switch ($err) {
                    case \MSG_ENOMSG:
                        return;

                    case 0:
                        break;

                    default:
                        $output->writeln(\sprintf('Can\'t get message. Error code: %d', $err));
                        return;
                }

                switch ($msgtype) {
                    case self::MSG_DIRECTORY:
                        $output->writeln(\sprintf(
                            'Index directory "%s"',
                            $data[0]
                        ));
                        $this->index->createDirectory(...$data);
                        break;

                    case self::MSG_FILE:
                        $output->writeln(\sprintf(
                            'Index file "%s"',
                            $data[0]
                        ));
                        $this->index->createFile(...$data);
                        break;
                }

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
        $this->index->flush();
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
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $output->writeln('Clear index');
        $this->index->clearIndex();

        $output->writeln('Fetch files and directories list from storage and index them');
        $this->indexDirectory($queue, $output, '/');
    }

    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output An OutputInterface instance.
     * @param string          $path   Path to indexed file.
     *
     * @return void
     */
    protected function indexDirectory($queue, OutputInterface $output, string $path)
    {
        $files = $this->adapter->listFiles($path);

        /** @var AdapterFile $file */
        foreach ($files as $file) {
            if ($file->isDirectory()) {
                //
                // We should wait until all job in queue is processed before add
                // new directory.
                //
                $this->waitQueue($queue);
                \msg_send($queue, self::MSG_DIRECTORY, [ $file->getPath() ]);

                //
                // Also wait until directory is created.
                //
                $this->waitQueue($queue);
                \sleep(2);
                $this->indexDirectory($queue, $output, $file->getPath());
            } else {
                \msg_send($queue, self::MSG_FILE, [ $file->getPath(), $file->getSize() ]);
            }
        }
    }
}
