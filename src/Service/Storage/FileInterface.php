<?php

namespace App\Service\Storage;

/**
 * Interface FileInterface
 *
 * Low level file.
 *
 * @package App\Service\Storage
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
