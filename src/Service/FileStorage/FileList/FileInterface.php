<?php

namespace App\Service\FileStorage\FileList;

/**
 * Interface FileInterface
 *
 * Low level file.
 *
 * @package App\Service\FileStorage\FileList
 * @deprecated See App\Service\Storage\FileInterface
 */
interface FileInterface
{

    /**
     * Return file name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return file size in bytes.
     *
     * @return integer
     */
    public function getSize(): int;

    /**
     * @return boolean
     */
    public function isDirectory(): bool;
}
