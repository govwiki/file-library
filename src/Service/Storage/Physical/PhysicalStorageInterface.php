<?php

namespace App\Service\Storage\Physical;

use Psr\Http\Message\StreamInterface;

/**
 * Interface PhysicalStorageInterface
 *
 * Interface represents "real" document storage where document are located.
 * For example filesystem.
 *
 * @package App\Service\Storage\Physical
 */
interface PhysicalStorageInterface
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
     * @return PhysicalFileListInterface
     */
    public function listFiles(string $publicPath = '/'): PhysicalFileListInterface;

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

    /**
     * Checks that file are exists.
     *
     * @param string $publicPath Public path to file.
     *
     * @return boolean
     */
    public function isExists(string $publicPath): bool;
}
