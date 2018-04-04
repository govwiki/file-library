<?php

namespace App\Repository;

use App\Entity\AbstractFile;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface FileRepository
 *
 * @package App\Repository
 */
class FileRepository extends EntityRepository implements FileRepositoryInterface
{

    /**
     * Find file by slug.
     *
     * @param string $slug File slug.
     *
     * @return AbstractFile|null
     */
    public function findBySlug(string $slug)
    {
        return $this->findOneBy([ 'slug' => $slug ]);
    }

    /**
     * @param string $publicPath A public path to file.
     *
     * @return AbstractFile|null
     */
    public function findByPublicPath(string $publicPath)
    {
        return $this->findOneBy([ 'publicPath' => $publicPath ]);
    }

    /**
     * @param string|null $publicPath Listed directory public path.
     *
     * @return QueryBuilder
     */
    public function listFilesIn(string $publicPath = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('File');

        if ($publicPath === null) {
            $qb
                ->where('File.parent IS NULL');
        } else {
            $qb
                ->join('File.parent', 'Parent')
                ->where('Parent.publicPath = :path')
                ->setParameter('path', $publicPath);
        }

        return $qb;
    }
}
