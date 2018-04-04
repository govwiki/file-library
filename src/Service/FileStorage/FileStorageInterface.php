<?php

namespace App\Service\FileStorage;

use App\Service\FileStorage\FileList\FileListInterface;
use Psr\Http\Message\StreamInterface;

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
     * @return string Public path to file.
     */
    public function store(
        string $src,
        string $dest
    ): string;

    /**
     * Get all files inside specified path.
     *
     * @param string $publicPath Public path to directory.
     *
     * @return FileListInterface
     */
    public function listFiles(string $publicPath = '/'): FileListInterface;

    /**
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath);

    /**
     * @param string $publicPath Public path to file.
     *
     * @return StreamInterface
     */
    public function read(string $publicPath): StreamInterface;
}
