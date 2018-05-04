<?php

namespace App\Service\Storage\Physical;

/**
 * Interface PhysicalFileListInterface
 *
 * List of files inside some directory of physical storage.
 *
 * @package App\Service\Storage\Physical
 */
interface PhysicalFileListInterface extends \Countable, \IteratorAggregate
{

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     * @psalm-return \Traversable<int, \App\Service\Storage\FileInterface>
     */
    public function getIterator(): \Traversable;
}
