<?php

namespace App\Storage;

use App\Storage\Adapter\StorageAdapterInterface;
use App\Storage\Index\StorageIndexInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Storage
 *
 * @package App\Storage
 */
class Storage
{
    /**
     * @var StorageAdapterInterface
     */
    private $adapter;

    /**
     * @var StorageIndexInterface
     */
    private $index;

    /**
     * Storage constructor.
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface instance.
     * @param StorageIndexInterface   $index   A StorageIndexInterface instance.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        StorageIndexInterface $index
    ) {
        $this->adapter = $adapter;
        $this->index = $index;
    }

    /**
     * Create new directory in storage.
     *
     * Create all specified parent directories if they not exists.
     *
     * @param string $path Path where directory should be placed.
     *
     * @return Directory
     *
     * @api
     */
    public function createDirectory(string $path): Directory
    {
        $this->adapter->createDirectory($path);
        $this->index->createDirectory($path)->flush();

        return new Directory(
            $this->adapter,
            $this->index,
            $path
        );
    }

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path)
    {
        if ($path !== '/') {
            //
            // Ignore root directory 'cause we don't index it.
            //
            $directory = $this->index->getDirectory($path);
            if ($directory === null) {
                return null;
            }
        }

        return new Directory($this->adapter, $this->index, $path);
    }

    /**
     * Create new file in storage.
     *
     * Create all specified parent directories if they not exists.
     *
     * @param string          $path   Path where file should be placed.
     * @param StreamInterface $stream File content.
     *
     * @return File
     * @api
     */
    public function createFile(string $path, StreamInterface $stream): File
    {
        $this->adapter->createFile($path, $stream);
        $this->index->createFile($path, $stream->getSize())->flush();

        return new File(
            $this->adapter,
            $this->index,
            $path,
            $stream->getSize(),
            $stream
        );
    }

    /**
     * @param string $path Path to required file.
     *
     * @return File|null
     */
    public function getFile(string $path)
    {
        $file = $this->index->getFile($path);
        if ($file === null) {
            return null;
        }

        return new File($this->adapter, $this->index, $path, $file->getFileSize());
    }

    /**
     * @param string $path Path to removed file.
     *
     * @return void
     */
    public function remove(string $path)
    {
        $this->adapter->remove($path);
        $this->index->remove($path)->flush();
    }

    /**
     * @return StorageIndexInterface
     */
    public function getIndex(): StorageIndexInterface
    {
        return $this->index;
    }

    /**
     * @return StorageAdapterInterface
     */
    public function getAdapter(): StorageAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param string $path Path to checked file.
     *
     * @return boolean
     */
    public function isFileExists(string $path): bool
    {
        return ($this->index->getFile($path) !== null) && $this->isFileExistInStorage($path);
    }

    /**
     * @param string $path Path to file.
     *
     * @return string
     */
    public function generatePublicUrl(string $path): string
    {
        return $this->adapter->generatePublicUrl($path);
    }

    /**
     * Checks for the existence of a file in the storage
     * @param $path
     * @return boolean
     */
    public function isFileExistInStorage(string $path):bool
    {
        return $this->adapter->isFileExists($path);
    }
}
