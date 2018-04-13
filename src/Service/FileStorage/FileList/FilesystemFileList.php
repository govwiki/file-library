<?php

namespace App\Service\FileStorage\FileList;

/**
 * Class FilesystemFileList
 *
 * @package App\Service\FileStorage\FileList
 */
class FilesystemFileList implements FileListInterface
{

    /**
     * @var \Iterator|null
     */
    private $iterator;

    /**
     * @string
     */
    private $path;

    /**
     * @var string|null
     */
    private $filterBy;

    /**
     * FilesystemFileList constructor.
     *
     * @param string $path Path to listed directory.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
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
     * @param boolean $showHidden Should hidden files displayed too.
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function showHidden(bool $showHidden)
    {
        // don't do anything.
        return $this;
    }

    /**
     * @param string $value Set filtering by file name.
     *
     * @return $this
     */
    public function filterBy(string $value)
    {
        $value = strtolower(trim($value));

        if ($this->filterBy !== $value) {
            $this->filterBy = $value === '' ? null : $value;
            $this->iterator = null;
        }

        return $this;
    }

    /**
     * Count elements of an object.
     *
     * @return integer
     */
    public function count(): int
    {
        return \count(iterator_to_array($this->iterator));
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        if ($this->iterator === null) {
            $this->iterator = new \DirectoryIterator($this->path);
            $this->iterator = new \CallbackFilterIterator($this->iterator, function (\DirectoryIterator $file): bool {
                $valid = ! $file->isDot();

                if ($valid && ($this->filterBy !== null)) {
                    $valid = stripos($file->getFilename(), $this->filterBy) !== false;
                }

                return $valid;
            });
        }
        return $this->iterator;
    }
}
