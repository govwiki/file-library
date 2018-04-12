<?php

namespace App\Service\FileStorage\FileList;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class ORMIndexFileList
 *
 * @package App\Service\FileStorage\FileList
 */
class ORMIndexFileList implements FileListInterface
{

    const SORTED_FIELDS = [ 'name', 'fileSize' ];

    /**
     * @var QueryBuilder
     */
    private $qb;

    /**
     * @var Paginator|null
     */
    private $paginator;

    /**
     * ORMIndexFileList constructor.
     *
     * @param QueryBuilder $qb A QueryBuilder instance.
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @param integer|null $limit Max file in result.
     *
     * @return $this
     */
    public function setLimit(int $limit = null)
    {
        /** @psalm-suppress PossiblyNullArgument */
        $this->qb->setMaxResults($limit);
        $this->paginator = null;

        return $this;
    }

    /**
     * @param integer|null $offset Offset from start.
     *
     * @return $this
     */
    public function setOffset(int $offset = null)
    {
        $this->qb->setFirstResult($offset ?? 0);
        $this->paginator = null;

        return $this;
    }

    /**
     * @param boolean $showHidden Should hidden files displayed too.
     *
     * @return $this
     */
    public function showHidden(bool $showHidden)
    {
        /** @var string $alias */
        $alias = $this->qb->getRootAliases()[0];

        if (! $showHidden) {
            $this->qb->andWhere($alias .'.hidden <> 1');
            $this->paginator = null;
        }

        return $this;
    }

    /**
     * @param array $fields Array of fields used for ordering.
     * @psalm-param array<string, string> $fields
     *
     * @return $this
     */
    public function orderBy(array $fields)
    {
        /** @var string $alias */
        $alias = $this->qb->getRootAliases()[0];

        // todo, priority: low. Clear order by statement before add new one
        foreach ($fields as $field => $order) {
            if (! \in_array($field, self::SORTED_FIELDS, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown field "%s" is using for sorting. Expects one of: %s',
                    $field,
                    self::SORTED_FIELDS
                ));
            }

            $order = strtolower($order);
            if (($order !== 'asc') && ($order !== 'desc')) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown order direction "%s". Expects "asc" or "desc"',
                    $order
                ));
            }

            $this->qb->addOrderBy(sprintf('%s.%s', $alias, $field), $order);
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
        return $this->buildPaginator()->count();
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return $this->buildPaginator();
    }

    /**
     * @return Paginator
     */
    private function buildPaginator(): Paginator
    {
        if ($this->paginator === null) {
            $this->paginator = new Paginator($this->qb, false);
        }

        return $this->paginator;
    }
}
