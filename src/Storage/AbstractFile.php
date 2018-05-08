<?php

namespace App\Storage;

use App\Storage\Adapter\File;
use App\Storage\Index\StorageIndexInterface;

/**
 * Class AbstractFile
 *
 * @package App\Storage
 */
abstract class AbstractFile
{

    /**
     * @var File\Directory|File\File
     */
    protected $file;

    /**
     * @var StorageIndexInterface
     */
    protected $index;

    /**
     * AbstractFile constructor.
     *
     * @param File\AbstractFile     $file  A AbstractAdapterFile instance.
     * @param StorageIndexInterface $index StorageIndexInterface instance.
     */
    public function __construct(
        File\AbstractFile $file,
        StorageIndexInterface $index
    ) {
        $this->file = $file;
        $this->index = $index;
    }

    /**
     * Get directory name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->file->getName();
    }

    /**
     * Get path to this directory.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->file->getPath();
    }

    /**
     * @return void
     */
    public function remove()
    {
        $this->index->remove($this->getPath());
        $this->file->remove();
    }

    /**
     * @param string $path Destination path.
     *
     * @return $this
     */
    public function move(string $path)
    {
        $this->file->move($path);
        $this->index->move($this->getPath(), $path);

        return $this;
    }
}
