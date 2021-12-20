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

    /**
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public function findByPage(int $page, int $limit):array;

    /**
     * @return integer
     */
    public function getCount():int;

    /**
     * @param User $user Persisted user.
     *
     * @return void
     */
    public function persist(User $user);

    /**
     * @param User $user Persisted user.
     *
     * @return void
     */
    public function delete(User $user);
}
