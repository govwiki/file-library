<?php

namespace App\Repository;

use App\Entity\Directory;
use Doctrine\ORM\EntityRepository;

/**
 * class DirectoryRepository
 *
 * @package App\Repository
 */
class DirectoryRepository extends EntityRepository implements DirectoryRepositoryInterface
{

    /**
     * Get directory by name and parent.
     *
     * @param string  $name   Directory name.
     * @param integer $parent Parent directory id.
     *
     * @return Directory|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getByNameAndParent(string $name, int $parent = null)
    {
        $criteria = [ 'name' => $name ];
        if ($parent !== null) {
            $criteria['parent'] = $parent;
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->findOneBy($criteria);
    }
}
