<?php

namespace App\Service\FileStorage;

use App\Service\FileStorage\FileList\FileListInterface;

/**
 * Class FilesystemFileList
 *
 * @package App\Service\FileStorage
 */
class FilesystemFileList implements FileListInterface
{

    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * FilesystemFileList constructor.
     *
     * @param string $path Path to listed directory.
     */
    public function __construct(string $path)
    {
        $this->iterator = new \DirectoryIterator($path);
        $this->iterator = new \CallbackFilterIterator($this->iterator, function (\DirectoryIterator $file) {
            return ! $file->isDot();
        });
    }

    /**
     * @param integer|null $limit Max file in result.
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setLimit(int $limit = null)
    {
        // don't do anything.
        return $this;
    }

    /**
     * @param integer|null $offset Offset from start.
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setOffset(int $offset = null)
    {
        // don't do anything.
        return $this;
    }

    /**
     * @param array $fields Array of fields used for ordering.
     * @psalm-param array<string, string> $fields
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function orderBy(array $fields)
    {
        // don't do anything.
        return $this;
    }

    /**
     * Count elements of an object.
     *
     * @return integer
     */
    public function count(): int
    {
        return count(iterator_to_array($this->iterator));
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }
}
