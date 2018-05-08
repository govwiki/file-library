<?php

namespace App\Storage\Adapter;

use App\Storage\Adapter\File\Directory;
use App\Storage\Adapter\File\File;
use Psr\Http\Message\StreamInterface;

/**
 * Interface StorageAdapterInterface
 *
 * @package App\Storage\Adapter
 */
interface StorageAdapterInterface
{

    /**
     * @param string $path Path to created directory.
     *
     * @return Directory
     *
     * @api
     */
    public function createDirectory(string $path): Directory;

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path);

    /**
     * @param string          $path    Path where file should be created.
     * @param StreamInterface $content Stored file content.
     *
     * @return File
     *
     * @api
     */
    public function createFile(string $path, StreamInterface $content): File;

    /**
     * @param string $path Path to required file.
     *
     * @return File|null
     */
    public function getFile(string $path);

    /**
     * Get list of files for specified path.
     *
     * @param string $path
     *
     * @return \Traversable
     * @psalm-return \Traversable<File\AbstractFile>
     *
     * @api
     */
    public function listFiles(string $path): \Traversable;

    /**
     * @param string $srcPath  Path from which we should move file.
     * @param string $destPath Path to which we should move.
     *
     * @return void
     *
     * @api
     */
    public function move(string $srcPath, string $destPath);

    /**
     * @param string $path A path to removed file.
     *
     * @return void
     *
     * @api
     */
    public function remove(string $path);

    /**
     * @param string $path A path to file.
     *
     * @return StreamInterface
     *
     * @api
     */
    public function read(string $path): StreamInterface;
}
