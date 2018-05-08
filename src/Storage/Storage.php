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
        return new Directory(
            $this->adapter->createDirectory($path),
            $this->index
        );
    }

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path)
    {
        $directory = $this->adapter->getDirectory($path);
        if ($directory === null) {
            return null;
        }

        return new Directory($directory, $this->index);
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
        return new File(
            $this->adapter->createFile($path, $stream),
            $this->index
        );
    }

    /**
     * @param string $path Path to required file.
     *
     * @return File|null
     */
    public function getFile(string $path)
    {
        $file = $this->adapter->getFile($path);
        if ($file === null) {
            return null;
        }

        return new File($file, $this->index);
    }

    /**
     * @param string $path Path to removed file.
     *
     * @return void
     */
    public function remove(string $path)
    {
        $this->adapter->remove($path);
        $this->index->remove($path);
    }
}
