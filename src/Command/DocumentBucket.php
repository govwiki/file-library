<?php

namespace App\Command;

use App\Entity\Document;
use App\Repository\DocumentRepositoryInterface;

/**
 * Class DocumentBucket
 *
 * @package App\Command
 */
class DocumentBucket
{

    /**
     * @var Document[]
     */
    private $bucket = [];

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @var integer
     */
    private $maxSize;

    /**
     * DocumentBucket constructor.
     *
     * @param DocumentRepositoryInterface $repository A DocumentRepositoryInterface
     *                                                instance.
     * @param integer                     $maxSize    A max bucket size before flush.
     */
    public function __construct(DocumentRepositoryInterface $repository, int $maxSize)
    {
        $this->repository = $repository;
        $this->maxSize = $maxSize;
    }

    /**
     * Attach new document to bucket.
     *
     * @param Document $document A attached document instance.
     *
     * @return $this
     */
    public function attach(Document $document)
    {
        $this->bucket[] = $document;

        if (count($this->bucket) === $this->maxSize) {
            $this->flush();
            $this->bucket = [];
        }

        return $this;
    }

    /**
     * Flush bucket into database.
     *
     * @return $this
     */
    public function flush()
    {
        if (count($this->bucket) > 0) {
            $this->repository->save($this->bucket);
            $this->bucket = [];
        }

        return $this;
    }
}
