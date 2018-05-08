<?php

namespace App\Storage\Adapter\File;

use App\Storage\Adapter\StorageAdapterInterface;

/**
 * Class AbstractFile
 *
 * @package App\Storage\Adapter\File
 */
abstract class AbstractFile
{

    /**
     * @var StorageAdapterInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $path;

    /**
     * AbstractFile constructor.
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface instance.
     * @param string                  $path    Path to file.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        string $path
    ) {
        $this->adapter = $adapter;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return \basename($this->path);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return void
     */
    public function remove()
    {
        $this->adapter->remove($this->path);
    }

    /**
     * @param string $path Destination path.
     *
     * @return $this
     */
    public function move(string $path)
    {
        $this->adapter->move($this->path, $path);

        return $this;
    }
}
