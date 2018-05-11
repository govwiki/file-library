<?php

namespace App\Storage;

use App\Storage\Adapter\StorageAdapterInterface;
use App\Storage\Index\StorageIndexInterface;

/**
 * Class AbstractFile
 *
 * @package App\Storage
 */
abstract class AbstractFile
{

    /**
     * @var StorageAdapterInterface
     */
    protected $adapter;

    /**
     * @var StorageIndexInterface
     */
    protected $index;

    /**
     * @var string
     */
    protected $path;

    /**
     * AbstractFile constructor.
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface instance.
     * @param StorageIndexInterface   $index   A StorageIndexInterface instance.
     * @param string                  $path    Path to file.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        StorageIndexInterface $index,
        string $path
    ) {
        $this->adapter = $adapter;
        $this->index = $index;
        $this->path = $path;
    }

    /**
     * Get directory name.
     *
     * @return string
     */
    public function getName(): string
    {
        return \basename($this->path);
    }

    /**
     * Get path to this directory.
     *
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
        $this->index->remove($this->path);
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
        $this->index->move($this->path, $path);

        return $this;
    }
}
