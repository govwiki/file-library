<?php

namespace App\Storage\Index;

use App\Entity\Directory;
use App\Entity\Document;
use App\Storage\FileListBuilderInterface;

/**
 * Interface StorageIndexInterface
 *
 * @package App\Storage\Index
 */
interface StorageIndexInterface
{

    /**
     * @param string $path Path to created directory.
     *
     * @return $this
     *
     * @api
     */
    public function createDirectory(string $path);

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path);

    /**
     * @param string  $path Path where file should be created.
     * @param integer $size Stored file size.
     *
     * @return $this
     *
     * @api
     */
    public function createFile(string $path, int $size);

    /**
     * @param string $path Path to required file.
     *
     * @return Document|null
     */
    public function getFile(string $path);

    /**
     * @param string|null $path Path to directory.
     *
     * @return FileListBuilderInterface
     */
    public function createFileListBuilder(string $path = null): FileListBuilderInterface;

    /**
     * @param string $srcPath  Path from which we should move file.
     * @param string $destPath Path to which we should move.
     *
     * @return $this
     *
     * @api
     */
    public function move(string $srcPath, string $destPath);

    /**
     * @param string $path A path to removed file.
     *
     * @return $this
     *
     * @api
     */
    public function remove(string $path);

    /**
     * Clear whole index.
     *
     * @return $this
     */
    public function clearIndex();

    /**
     * Flush changes.
     *
     * @return $this
     */
    public function flush();
}
