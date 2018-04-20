<?php

namespace App\Service\FileStorage\FileList;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * Class ORMIndexFileList
 *
 * @package App\Service\FileStorage\FileList
 */
class ORMIndexFileList implements FileListInterface
{

    const SORTED_FIELDS = [ 'name', 'fileSize' ];

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string|null
     */
    private $publicPath;

    /**
     * @var integer|null
     */
    private $limit;

    /**
     * @var integer
     */
    private $offset = 0;

    /**
     * @var boolean
     */
    private $showHidden = false;

    /**
     * @var array<string, string>
     */
    private $order = [];

    /**
     * @var string
     */
    private $filter = '';

    /**
     * @var boolean
     */
    private $onlyDocuments = false;

    /**
     * @var boolean
     */
    private $recursive = false;

    /**
     * @var QueryBuilder|null
     */
    private $qb;

    /**
     * @var integer|null
     */
    private $count;

    /**
     * ORMIndexFileList constructor.
     *
     * @param EntityManagerInterface $em         A EntityManagerInterface instance.
     * @param string|null            $publicPath A path from which we should get
     *                                           list of files.
     */
    public function __construct(EntityManagerInterface $em, string $publicPath = null)
    {
        $this->em = $em;
        $this->publicPath = $publicPath;
    }

    /**
     * @param integer|null $limit Max file in result.
     *
     * @return $this
     */
    public function setLimit(int $limit = null)
    {
        if ($this->limit !== $limit) {
            $this->markAsDirty();
        }
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param integer|null $offset Offset from start.
     *
     * @return $this
     */
    public function setOffset(int $offset = null)
    {
        $offset = $offset ?? 0;
        if ($this->offset !== $offset) {
            $this->markAsDirty();
        }
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param boolean $showHidden Should hidden files displayed too.
     *
     * @return $this
     */
    public function showHidden(bool $showHidden)
    {
        if ($this->showHidden !== $showHidden) {
            $this->markAsDirty();
        }
        $this->showHidden = $showHidden;

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
        foreach ($fields as $field => $direction) {
            $direction = \strtoupper($direction);
            if (! \in_array($field, self::SORTED_FIELDS, true)) {
                throw new \DomainException(\sprintf(
                    'Unknown field "%s", expects one of %s',
                    $field,
                    \implode(', ', self::SORTED_FIELDS)
                ));
            }

            if (($direction !== 'ASC') && ($direction !== 'DESC')) {
                throw new \DomainException(\sprintf(
                    'Unknown ordering direction "%s", expects one of asc, desc',
                    $direction
                ));
            }
        }
        $this->order = $fields;
        $this->markAsDirty(); // todo low priority, make deep comparison between current and drop query builder new and only if they different

        return $this;
    }

    /**
     * @param string $value Set filtering by file name.
     *
     * @return $this
     */
    public function filterBy(string $value)
    {
        if ($this->filter !== $value) {
            $this->markAsDirty();
        }
        $this->filter = $value;

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
            $this->markAsDirty();
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
            $this->markAsDirty();
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
        if ($this->count === null) {
            $countQb = clone $this->buildQueryBuilder();

            /** @psalm-suppress NullArgument */
            $this->count = (int) current($countQb
                ->select('COUNT(File.id)')
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->getQuery()
                ->getSingleResult());
        }

        return $this->count;
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        $qb = $this->buildQueryBuilder();

        foreach ($this->order as $field => $direction) {
            $qb->addOrderBy('File.'. $field, $direction);
        }

        return new \ArrayIterator($qb->getQuery()->getResult());
    }

    /**
     * @return QueryBuilder
     */
    private function buildQueryBuilder(): QueryBuilder
    {
        if ($this->qb === null) {
            $qb = $this->recursive
                ? $this->buildForRecursive()
                : $this->buildForNonRecursive();

            /** @psalm-suppress PossiblyNullArgument */
            $qb
                ->setMaxResults($this->limit)
                ->setFirstResult($this->offset);

            if (! $this->showHidden) {
                $qb->andWhere('File.hidden <> 1');
            }

            if ($this->onlyDocuments) {
                $qb->andWhere('File INSTANCE OF '. Document::class);
            }

            if ($this->filter !== '') {
                $qb
                    ->andWhere('File.name LIKE :name')
                    ->setParameter('name', '%'. preg_replace('/\s+/', '%', $this->filter) .'%');
            }

            $this->qb = $qb;
        }

        return $this->qb;
    }

    /**
     * @return void
     */
    private function markAsDirty()
    {
        $this->count = null;
        $this->qb = null;
    }

    /**
     * @return QueryBuilder
     */
    private function buildForRecursive(): QueryBuilder
    {
        if ($this->publicPath === null) {
            return $this->em->createQueryBuilder()
                ->select('File')
                ->from(AbstractFile::class, 'File');
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->em->getClassMetadata(Directory::class);
        $tableName = $metadata->getTableName();

        $nestedDirsIds = $this->em->getConnection()->executeQuery("
            SELECT id
            FROM
              (
                SELECT id, parent_id, `type`
                FROM {$tableName}
              ) x,
              (
                SELECT @pv := (
                  SELECT id
                  FROM {$tableName}
                  WHERE public_path = '{$this->publicPath}'
                )
              ) initialization
            WHERE
              FIND_IN_SET(parent_id, @pv) > 0
              AND `type` = 'directory'
              AND @pv := CONCAT(@pv, ',', id)
        ")->fetchAll();

        if (count($nestedDirsIds) === 0) {
            return $this->buildForNonRecursive();
        }

        return $this->em->createQueryBuilder()
            ->select('File')
            ->from(AbstractFile::class, 'File')
            ->where('File.parent in (:ids)')
            ->setParameter('ids', $nestedDirsIds);
    }

    /**
     * @return QueryBuilder
     */
    private function buildForNonRecursive(): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->select('File')
            ->from(AbstractFile::class, 'File');

        if ($this->publicPath === null) {
            $qb
                ->where('File.parent IS NULL');
        } else {
            $qb
                ->join('File.parent', 'Parent')
                ->where('Parent.publicPath = :path')
                ->setParameter('path', $this->publicPath);
        }

        return $qb;
    }
}
