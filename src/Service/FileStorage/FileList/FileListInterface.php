<?php

namespace App\Service\FileStorage\FileList;

/**
 * interface FileListInterface
 *
 * @package App\Service\FileStorage\FileList
 */
interface FileListInterface extends \Countable, \IteratorAggregate
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
}
