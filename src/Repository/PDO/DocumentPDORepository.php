<?php

namespace App\Repository\PDO;

use App\Model\Document;
use App\Repository\DocumentRepositoryInterface;

/**
 * Class DocumentPDORepository
 *
 * @package App\Repository\PDO
 */
class DocumentPDORepository extends AbstractPDORepository implements DocumentRepositoryInterface
{

    /**
     * Get all available document repositories.
     *
     * @return string[]
     * @psalm-return array<string, string>
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getTypes(): array
    {
        $stmt = $this->execute('
            SELECT type, type_slug FROM documents
            GROUP BY type, type_slug
            ORDER BY type
        ');

        $types = [];
        /** @var array{type: string, type_slug: string} $row */
        foreach ($stmt->fetchAll() as $row) {
            $types[$row['type_slug']] = $row['type'];
        }

        return $types;
    }

    /**
     * Get all available document states for specified type.
     *
     * @param string $type A document type for which we should get states.
     *
     * @return string[]
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getStates(string $type): array
    {
        $stmt = $this->execute('
            SELECT state FROM documents
            WHERE
                type = :type OR
                type_slug = :type
            GROUP BY state
            ORDER BY state
        ', [
            'type' => $type,
        ]);

        /** @psalm-suppress LessSpecificReturnStatement */
        return array_map('current', $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param string $type  A document type or slug for which we should get years.
     * @param string $state A document state or slug for which we should get years.
     *
     * @return integer[]
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getYears(string $type, string $state): array
    {
        $stmt = $this->execute('
            SELECT year FROM documents
            WHERE
                (
                    type = :type OR
                    type_slug = :type
                ) AND
                state = :state
            GROUP BY year
            ORDER BY year
        ', [
            'type' => $type,
            'state' => $state,
        ]);

        /** @psalm-suppress LessSpecificReturnStatement */
        return array_map('current', $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @param string $type  A document type for which we should get documents.
     * @param string $state A document state for which we should get documents.
     * @param string $year  A document year for which we should get documents.
     *
     * @return Document[]
     */
    public function getDocuments(string $type, string $state, string $year): array
    {
        $stmt = $this->execute('
            SELECT * FROM documents
            WHERE
                (
                    type = :type OR
                    type_slug = :type
                ) AND
                state = :state AND
                year = :year
            ORDER BY name
        ', [
            'type' => $type,
            'state' => $state,
            'year' => $year,
        ]);

        /** @var array[] $data */
        $data = $stmt->fetchAll();

        return $this->hydrateCollection(Document::class, $data);
    }

    /**
     * @param string $slug A Document slug.
     *
     * @return Document
     */
    public function getBySlug(string $slug): Document
    {
        $stmt = $this->execute('
            SELECT * FROM documents
            WHERE
                slug = :slug
        ', [
            'slug' => $slug,
        ]);

        /** @var array|boolean $data */
        $data = $stmt->fetch();
        if (! is_array($data)) {
            $error = $this->pdo->errorInfo();
            throw new \RuntimeException(sprintf(
                'Can\'t get document by slug. MySQL error %s: %s',
                $error[0],
                $error[2]
            ));
        }

        return $this->hydrate(Document::class, $data);
    }

    /**
     * @param Document|Document[] $documents A saved document or array of
     *                                       documents.
     *
     * @return void
     */
    public function save($documents)
    {
        if ($documents instanceof Document) {
            $documents = [ $documents ];
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (! is_array($documents)) {
            throw new \InvalidArgumentException('$documents should be Document instance or array of instances');
        }

        if (count($documents) === 0) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            /** @var Document $document */
            foreach ($documents as $document) {
                $user = $document->getUploadedBy();

                $sql = sprintf(
                    '
                        INSERT IGNORE INTO documents
                        (slug, type_slug, name, type, state, year, path, file_size, uploaded_at, uploaded_by_id)
                        VALUES
                        (%s, %s, %s, %s, %s, %d, %s, %d, %s, %s)
                    ',
                    $this->pdo->quote($document->getSlug()),
                    $this->pdo->quote($document->getTypeSlug()),
                    $this->pdo->quote($document->getName()),
                    $this->pdo->quote($document->getType()),
                    $this->pdo->quote($document->getState()),
                    $document->getYear(),
                    $this->pdo->quote($document->getPath()),
                    $document->getFileSize(),
                    $this->pdo->quote($document->getUploadedAt()->format('Y-m-d H:i:s')),
                    $user !== null ? $user->getId() : 'null'
                );

                $result = $this->pdo->exec($sql);

                /** @psalm-suppress RedundantCondition */
                if (! is_int($result)) {
                    $error = $this->pdo->errorInfo();
                    throw new \RuntimeException(sprintf(
                        'Can\'t execute sql "%s".MySQL error %s: %s',
                        $sql,
                        $error[0],
                        $error[2]
                    ));
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw new \RuntimeException(sprintf(
                'Can\'t save documents due to %s',
                $exception->getMessage()
            ), $exception->getCode(), $exception);
        }
    }
}
