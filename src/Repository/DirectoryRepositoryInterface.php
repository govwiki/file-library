<?php

namespace App\Repository;

use App\Entity\Directory;

/**
 * Interface DirectoryRepositoryInterface
 *
 * @package App\Repository
 */
interface DirectoryRepositoryInterface
{

    /**
     * Get directory by name and parent.
     *
     * @param string  $name   Directory name.
     * @param integer $parent Parent directory id.
     *
     * @return Directory|null
     */
    public function getByNameAndParent(string $name, int $parent = null);
}
