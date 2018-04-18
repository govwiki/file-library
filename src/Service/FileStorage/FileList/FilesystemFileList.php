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
     * @var string
     */
    private $path;

    /**
     * @var string|null
     */
    private $filterBy;

    /**
     * @var boolean
     */
    private $onlyDocuments = false;

    /**
     * @var boolean
     */
    private $recursive = false;

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
     * @param boolean $onlyDocuments Fetch only documents without directory.
     *
     * @return $this
     */
    public function onlyDocuments(bool $onlyDocuments = true)
    {
        if ($this->onlyDocuments !== $onlyDocuments) {
            $this->iterator = null;
        }
        $this->onlyDocuments = $onlyDocuments;

        return $this;
    }

    /**
     * @param boolean $recursive Recursively fetch all files.
     *
     * @return $this
     */
    public function recursive(bool $recursive = true)
    {
        if ($this->recursive !== $recursive) {
            $this->iterator = null;
        }
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * Count elements of an object.
     *
     * @return integer
     */
    public function count(): int
    {
        return \count(iterator_to_array($this->getIterator()));
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        if ($this->iterator === null) {
            if ($this->recursive) {
                $this->iterator = new \RecursiveDirectoryIterator($this->path);
                $this->iterator = new \RecursiveIteratorIterator($this->iterator);
            } else {
                $this->iterator = new \DirectoryIterator($this->path);
            }

            $this->iterator = new \CallbackFilterIterator($this->iterator, function (\DirectoryIterator $file): bool {
                $valid = ! $file->isDot();

                if ($this->onlyDocuments) {
                    $valid = $valid && $file->isFile();
                }

                if ($valid && ($this->filterBy !== null)) {
                    $valid = stripos($file->getFilename(), $this->filterBy) !== false;
                }

                return $valid;
            });
        }
        return $this->iterator;
    }
}
