<?php

namespace App\Repository;

use App\Entity\AbstractFile;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface FileRepositoryInterface
 *
 * @package App\Repository
 */
interface FileRepositoryInterface
{

    /**
     * Default limit for files per page.
     */
    const DEFAULT_LIMIT = 20;

    /**
     * Find file by slug.
     *
     * @param string $slug File slug.
     *
     * @return AbstractFile|null
     */
    public function findBySlug(string $slug);

    /**
     * @param string $publicPath A public path to file.
     *
     * @return AbstractFile|null
     */
    public function findByPublicPath(string $publicPath);

    /**
     * @param string|null $publicPath Listed directory public path.
     *
     * @return QueryBuilder
     */
    public function listFilesIn(string $publicPath = null): QueryBuilder;
}
