<?php

namespace App\Storage\Index;

use App\Storage\FileListBuilderInterface;

/**
 * Interface StorageIndexInterface
 *
 * @package App\Storage\Index
 */
interface StorageIndexInterface
{

    /**
     * @param string $path Path to directory.
     *
     * @return FileListBuilderInterface
     */
    public function createFileListBuilder(string $path): FileListBuilderInterface;

    /**
     * @param string $srcPath  Path to moved file.
     * @param string $destPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPath, string $destPath);

    /**
     * @param string  $path        Path to indexed file.
     * @param boolean $isDirectory True if indexed file is directory.
     * @param integer $size        Size of indexed file, make sense only for documents.
     *
     * @return void
     */
    public function index(string $path, bool $isDirectory, int $size = 0);

    /**
     * @param string  $path        Path to indexed file.
     * @param boolean $isDirectory True if indexed file is directory.
     * @param integer $size        Size of indexed file, make sense only for documents.
     *
     * @return void
     *
     * @see StorageIndexInterface::flush()
     */
    public function deferIndex(string $path, bool $isDirectory, int $size = 0);

    /**
     * @param string $path A removed indexed file.
     *
     * @return void
     */
    public function remove(string $path);

    /**
     * Clear whole index.
     *
     * @return void
     */
    public function clearIndex();

    /**
     * Flush changes.
     *
     * @return void
     */
    public function flush();
}
