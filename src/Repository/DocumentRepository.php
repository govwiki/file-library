<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;

/**
 * Class DocumentRepository
 *
 * @package App\Repository
 */
class DocumentRepository extends EntityRepository implements DocumentRepositoryInterface
{

    /**
     * Get all available document repositories.
     *
     * @return string[]
     * @psalm-return Array<string, string>
     */
    public function getTypes(): array
    {
        /** @var array<int, array{type: string, typeSlug: string}> $rows */
        $rows = $this->createQueryBuilder('Document')
            ->select('Document.type, Document.typeSlug')
            ->groupBy('Document.type')
            ->addGroupBy('Document.typeSlug')
            ->orderBy('Document.type')
            ->getQuery()
            ->getArrayResult();

        $types = [];

        foreach ($rows as $row) {
            $types[$row['typeSlug']] = $row['type'];
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return $types;
    }

    /**
     * Get document type by type slug.
     *
     * @param string $typeSlug Required type slug.
     *
     * @return string
     */
    public function getTypeByTypeSlug(string $typeSlug): string
    {
        return (string) $this->createQueryBuilder('Document')
            ->select('Document.type')
            ->where('Document.typeSlug = :type')
            ->setParameter('type', $typeSlug)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all available document states for specified type.
     *
     * @param string $type A document type or type slug for which we should get
     *                     states.
     *
     * @return string[]
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getStates(string $type): array
    {
        /** @var string[] $rows */
        $rows = $this->createQueryBuilder('Document')
            ->select('Document.state')
            ->groupBy('Document.state')
            ->orderBy('Document.state')
            ->where('Document.type = :type OR Document.typeSlug = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult();

        /**
         * @psalm-suppress LessSpecificReturnStatement
         * @psalm-suppress InvalidArgument
         */
        return array_map('current', $rows);
    }

    /**
     * @param string $type  A document type for which we should get years.
     * @param string $state A document state for which we should get years.
     *
     * @return integer[]
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getYears(string $type, string $state): array
    {
        /** @var integer[] $rows */
        $rows = $this->createQueryBuilder('Document')
            ->select('Document.year')
            ->groupBy('Document.year')
            ->orderBy('Document.year')
            ->where('
                (
                    Document.type = :type
                    OR Document.typeSlug = :type
                )
                AND Document.state = :state
            ')
            ->setParameters(new ArrayCollection([
                new Parameter('type', $type),
                new Parameter('state', $state),
            ]))
            ->getQuery()
            ->getArrayResult();

        /**
         * @psalm-suppress LessSpecificReturnStatement
         * @psalm-suppress InvalidArgument
         */
        return array_map('current', $rows);
    }

    /**
     * @param string   $type   A document type for which we should get
     *                         documents.
     * @param string   $state  A document state for which we should get
     *                         documents.
     * @param string   $year   A document year for which we should get
     *                         documents.
     * @param string[] $order  In which order document should fetching.
     * @param integer  $offset Offset from start of documents.
     * @param integer  $limit  Required documents per response.
     *
     * @return DocumentCollection
     */
    public function getDocuments(
        string $type,
        string $state,
        string $year,
        array $order = [],
        int $offset = 0,
        int $limit = null
    ): DocumentCollection {
        $qb = $this->createQueryBuilder('Document')
            ->where('
                (
                    Document.type = :type
                    OR Document.typeSlug = :type
                )
                AND Document.state = :state
                AND Document.year = :year
            ')
            ->setParameters(new ArrayCollection([
                new Parameter('type', $type),
                new Parameter('state', $state),
                new Parameter('year', $year),
            ]));

        /**
         * @var string $property
         * @var string $dir
         */
        foreach ($order as $property => $dir) {
            $qb->addOrderBy('Document.'. $property, $dir);
        }

        $totalCount = null;
        if ($limit !== null) {
            $countQb = clone $qb;
            $totalCount = (int) $countQb
                ->select('COUNT(Document.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $qb->setMaxResults($limit);
        }

        $documents = $qb->getQuery()->setFirstResult($offset)->getResult();

        return new DocumentCollection($documents, $totalCount ?? count($documents));
    }

    /**
     * @param string $slug A Document slug.
     *
     * @return Document|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getBySlug(string $slug)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->findOneBy([ 'slug' => $slug ]);
    }

    /**
     * Save passed document or documents into storage.
     * If storage already has document with some name this document will be
     * ignored.
     *
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

        $this->_em->transactional(function (EntityManagerInterface $em) use ($documents) {
            /** @var Document $document */
            foreach ($documents as $document) {
                $em->persist($document);
            }
        });
    }

    /**
     * @param Document $document A removed document.
     *
     * @return void
     */
    public function remove(Document $document)
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}
