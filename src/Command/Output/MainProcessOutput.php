<?php

namespace App\Command\Output;


/**
 * Class MainProcessOutput
 *
 * @package App\Command\Output
 */
class MainProcessOutput extends AbstractOutputDecorator
{

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
                return '[MAIN] '. $message;
            }, $messages);
        } else {
            $messages = '[MAIN] '. $messages;
        }

        parent::writeln($messages, $options);
    }
}
