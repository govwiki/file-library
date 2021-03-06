<?php

namespace App\Storage;

/**
 * Interface FileListBuilderInterface
 *
 * @package App\Storage
 */
interface FileListBuilderInterface extends \Countable, \IteratorAggregate
{

    /**
     * @param integer|null $limit Max file in result.
     *
     * @return $this
     */
    public function setLimit(int $limit = null);

    /**
     * @param integer|null $offset Offset from start.
     *
     * @return $this
     */
    public function setOffset(int $offset = null);

    /**
     * @param array $fields Array of fields used for ordering.
     * @psalm-param array<string, string> $fields
     *
     * @return $this
     */
    public function orderBy(array $fields);

    /**
     * @param boolean $showHidden Should hidden files displayed too.
     *
     * @return $this
     */
    public function showHidden(bool $showHidden);

    /**
     * @param string $value Set filtering by file name.
     *
     * @return $this
     */
    public function filterBy(string $value);

    /**
     * @param string $value
     *
     * @return $this
     */
    public function filterByState(string $value);

    /**
     * @param boolean $onlyDocuments Fetch only documents without directory.
     *
     * @return $this
     */
    public function onlyDocuments(bool $onlyDocuments = true);

    /**
     * @param boolean $recursive Recursively fetch all files.
     *
     * @return $this
     */
    public function recursive(bool $recursive = true);

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     * @psalm-return \Traversable<int, FileInterface>
     */
    public function getIterator(): \Traversable;
}
