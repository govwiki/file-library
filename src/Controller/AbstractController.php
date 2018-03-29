<?php

namespace App\Controller;

/**
 * Class AbstractController
 *
 * @package App\Controller
 */
abstract class AbstractController
{


    /**
     * @param array  $args A array of arguments.
     * @param string $name A required argument name.
     *
     * @return string
     *
     * @throws \InvalidArgumentException If required parameter not found.
     */
    protected function getArgument(array $args, string $name): string
    {
        if (! isset($args[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Can\'t find required parameter "%s"',
                $name
            ));
        }

        /** @psalm-suppress MixedAssignment */
        $argument = $args[$name];

        if (! is_string($argument)) {
            throw new \InvalidArgumentException(sprintf(
                'Parameter "%s" should be string',
                $name
            ));
        }

        /** @psalm-suppress MixedReturnStatement */
        return $argument;
    }
}
