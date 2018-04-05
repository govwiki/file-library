<?php

namespace App\Service\FileStorage\Index;

use App\Service\FileStorage\FileList\FileListInterface;

/**
 * Interface FileStorageIndexInterface
 *
 * @package App\Service\FileStorage\Index
 */
interface FileStorageIndexInterface
{

    /**
     * Get all files inside specified path.
     *
     * @param string|null $publicPath Public path to directory.
     *
     * @return FileListInterface
     */
    public function createList(string $publicPath = null): FileListInterface;

    /**
     * Add specified file to index.
     *
     * @param string  $publicPath  Public path to indexed file.
     * @param boolean $isDirectory Index as directory if set.
     * @param integer $fileSize    Indexed file size. Ignored if $isDirectory is
     *                             true.
     *
     * @return $this
     */
    public function index(string $publicPath, bool $isDirectory = true, int $fileSize = 0);

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return $this
     */
    public function move(string $srcPublicPath, string $destPublicPath);

    /**
     * Remove specified file from index.
     *
     * @param string $publicPath Public path to removed file.
     *
     * @return $this
     */
    public function remove(string $publicPath);

    /**
     * Clear index.
     *
     * @return $this
     */
    public function clear();

    /**
     * Apply index changes.
     *
     * @return $this
     */
    public function flush();
}
