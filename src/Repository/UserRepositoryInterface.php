<?php

namespace App\Repository;

use App\Entity\User;

/**
 * Interface UserRepositoryInterface
 *
 * @package App\Repository
 */
interface UserRepositoryInterface
{

    /**
     * @param string $username Username.
     *
     * @return User|null
     */
    public function findByUsername(string $username);
}
