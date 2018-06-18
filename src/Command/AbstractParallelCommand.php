<?php

namespace App\Command;

use App\Command\Output\MainProcessOutput;
use App\Command\Output\WorkerProcessOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * Class AbstractParallelCommand
 *
 * @package App\Command
 */
abstract class AbstractParallelCommand extends Command
{

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Concurrency connection count');
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
        try {
            $store = new SemaphoreStore();
            $factory = new Factory($store);
            $lock = $factory->createLock($this->getName());
            if (! $lock->acquire()) {
                $output->writeln('Command already run.');

                exit(0);
            }

            $concurrency = $input->getOption('concurrency');
            if ($concurrency === null) {
                $concurrency = $this->detectCpuNumber();
            }

            //
            // Start child process.
            //
            $childs = [];

            $mainOutput = new MainProcessOutput($output);

            $this->doInitialize($input, $mainOutput);

            $mainOutput->writeln(\sprintf('Spawn %d childs', $concurrency));

            for ($i = 0; $i < $concurrency; ++$i) {
                $pid = \pcntl_fork();
                switch ($pid) {
                    case -1:
                        $output->writeln('Can\'t for new child');

                        return 1;

                    case 0:
                        $pid = \posix_getpid();
                        $queue = \msg_get_queue($this->computeKey($this->getName()));

                        $this->childProcess($queue, new WorkerProcessOutput($output, $pid));

                        break;

                    default:
                        $childs[] = $pid;
                }
            }

            $queue = \msg_get_queue($this->computeKey($this->getName()));
            $this->mainProcess($queue, $mainOutput);

            $this->waitQueue($queue);
            \msg_remove_queue($queue);

            $mainOutput->writeln('Stop all workers \'cause queue is empty');
            foreach ($childs as $child) {
                \posix_kill($child, \SIGTERM);
            }

            $status = null;
            \pcntl_wait($status);

            $this->doFinalize($input, $mainOutput);

        } finally {
            $lock->release();
        }

        return 0;
    }

    /**
     * Wait until all messages from queue was consumed.
     *
     * @param resource $queue The queue.
     *
     * @return void
     */
    protected function waitQueue($queue)
    {
        for (;\msg_stat_queue($queue)['msg_qnum'] > 0; \sleep(1)) {}
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
        // do nothing
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
        // do nothing
    }

    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    abstract protected function childProcess($queue, OutputInterface $output);

    /**
     * @param resource        $queue  Processed queue.
     * @param OutputInterface $output A OutputInterface instance.
     *
     * @return void
     */
    abstract protected function mainProcess($queue, OutputInterface $output);

    /**
     * @return integer
     */
    private function detectCpuNumber(): int
    {
        $numCPUs = 1;

        if (\is_file('/proc/cpuinfo'))
        {
            $cpuInfo = \file_get_contents('/proc/cpuinfo');
            \preg_match_all('/^processor/m', $cpuInfo, $matches);
            $numCPUs = \count($matches[0]);
        }
        elseif (stripos(PHP_OS, 'WIN') === 0) {
            $process = \popen('wmic cpu get NumberOfCores', 'rb');
            if (false !== $process)
            {
                \fgets($process);
                $numCPUs = (int) \fgets($process);
                \pclose($process);
            }
        }
        else {
            $process = \popen('sysctl -a', 'rb');
            if (false !== $process)
            {
                $output = \stream_get_contents($process);
                \preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    $numCPUs = (int) $matches[1][0];
                }
                \pclose($process);
            }
        }

        return $numCPUs;
    }

    /**
     * @param string $key Some string key.
     *
     * @return integer
     */
    private function computeKey(string $key): int
    {
        $result = 1;
        $length = \strlen($key);

        for ($i = 0; $i < $length; ++$i) {
            $result = \ord($key) / $result;
        }

        return (int) \round($result);
    }
}
