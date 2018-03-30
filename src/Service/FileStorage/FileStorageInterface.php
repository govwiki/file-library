<?php

namespace App\Service\FileStorage;

/**
 * Interface FileStorageInterface
 *
 * @package App\Service\FileStorage
 */
interface FileStorageInterface
{

    /**
     * @param string $src  Source file path.
     * @param string $dest Destination file path.
     *
     * @return string Stored file unique key.
     */
    public function store(
        string $src,
        string $dest
    ): string;

    /**
     * @param string $uniqueKey Removed file unique key.
     *
     * @return void
     */
    public function remove(string $uniqueKey);
}
