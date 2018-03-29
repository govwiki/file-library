<?php

namespace App\Repository;

use App\Entity\Document;

/**
 * Class DocumentCollection
 *
 * @package App\Repository
 */
class DocumentCollection
{

    /**
     * @var Document[]
     */
    private $documents;

    /**
     * @var integer
     */
    private $totalCount;

    /**
     * DocumentCollection constructor.
     *
     * @param Document[] $documents  Array of documents.
     * @param integer    $totalCount Total document count.
     */
    public function __construct(array $documents, int $totalCount)
    {
        $this->documents = $documents;
        $this->totalCount = $totalCount;
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @return integer
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return integer
     */
    public function getFilteredCount(): int
    {
        return $this->totalCount;
    }
}
