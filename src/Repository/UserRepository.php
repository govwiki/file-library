<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 *
 * @package App\Repository
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{

    /**
     * @param string $username Username.
     *
     * @return User|null
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findByUsername(string $username)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->find($username);
    }

    /**
     * @param User $user Persisted user.
     *
     * @return void
     */
    public function persist(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush($user);
    }
}
