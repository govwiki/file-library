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
     * @param string $id File id.
     *
     * @return AbstractFile|null
     */
    public function findById(string $id);

    /**
     * Find file by slug.
     *
     * @param string $slug File slug.
     *
     * @return AbstractFile|null
     */
    public function findBySlug(string $slug);

    /**
     * @return string[]
     * @psalm-return Array<int, string>
     */
    public function getTopLevelDirNames(): array;

    /**
     * @param string $publicPath A public path to file.
     *
     * @return AbstractFile|null
     */
    public function findByPublicPath(string $publicPath);
}
