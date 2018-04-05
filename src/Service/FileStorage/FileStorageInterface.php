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
     * @param StreamInterface $stream         Source file content as stream.
     * @param string          $destPublicPath Destination file path.
     *
     * @return void
     */
    public function store(StreamInterface $stream, string $destPublicPath);

    /**
     * Get all files inside specified path.
     *
     * @param string $publicPath Public path to directory.
     *
     * @return FileListInterface
     */
    public function listFiles(string $publicPath = '/'): FileListInterface;

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPublicPath, string $destPublicPath);

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
