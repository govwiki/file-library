<?php

namespace App\Command\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WorkerProcessOutput
 *
 * @package App\Command\Output
 */
class WorkerProcessOutput extends AbstractOutputDecorator
{

    /**
     * @var integer
     */
    private $pid;

    /**
     * MainProcessOutput constructor.
     *
     * @param OutputInterface $internal Decorated OutputInterface instance.
     * @param integer         $pid      Worker pid.
     */
    public function __construct(OutputInterface $internal, int $pid)
    {
        parent::__construct($internal);

        $this->pid = $pid;
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int          $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, $options = 0)
    {
        if (\is_array($messages)) {
            $messages = \array_map(function (string $message): string {
                return \sprintf('[WORKER %d] %s', $this->pid, $message);
            }, $messages);
        } else {
            $messages = \sprintf('[WORKER %d] %s', $this->pid, $messages);
        }

        parent::writeln($messages, $options);
    }
}
