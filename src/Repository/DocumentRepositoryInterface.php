<?php

namespace App\Repository;

use App\Entity\Document;

/**
 * Interface DocumentRepositoryInterface
 *
 * @package App\Repository
 */
interface DocumentRepositoryInterface
{

    /**
     * Get all available document repositories.
     *
     * @return string[]
     */
    public function getTypes(): array;

    /**
     * Get document type by type slug.
     *
     * @param string $typeSlug Required type slug.
     *
     * @return string
     */
    public function getTypeByTypeSlug(string $typeSlug): string;

    /**
     * Get all available document states for specified type.
     *
     * @param string $type A document type or type slug for which we should get states.
     *
     * @return string[]
     */
    public function getStates(string $type): array;

    /**
     * @param string $type  A document type for which we should get years.
     * @param string $state A document state for which we should get years.
     *
     * @return integer[]
     */
    public function getYears(string $type, string $state): array;

    /**
     * @param string   $type   A document type for which we should get documents.
     * @param string   $state  A document state for which we should get documents.
     * @param string   $year   A document year for which we should get documents.
     * @param string[] $order  In which order document should fetching.
     * @param integer  $offset Offset from start of documents.
     * @param integer  $limit  Required documents per response.
     *
     * @return Document[]
     */
    public function getDocuments(
        string $type,
        string $state,
        string $year,
        array $order = [],
        int $offset = 0,
        int $limit = null
    ): array;

    /**
     * @param string $slug A Document slug.
     *
     * @return Document|null
     */
    public function getBySlug(string $slug);

    /**
     * Save passed document or documents into storage.
     * If storage already has document with some name this document will be ignored.
     *
     * @param Document|Document[] $documents A saved document or array of documents.
     *
     * @return void
     */
    public function save($documents);

    /**
     * @param Document $document A removed document.
     *
     * @return void
     */
    public function remove(Document $document);
}
