<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

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
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findByPage(int $page = 1, int $limit = 20):array
    {
        return $this
            ->createQueryBuilder('u')
            ->setFirstResult($limit * ($page - 1) )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        try {
            return (int) $this->createQueryBuilder('u')
                ->select('COUNT(u.username) as count_user')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
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

    /**
     * @param User $user Delete user.
     *
     * @return void
     */
    public function delete(User $user)
    {
        $this->_em->remove($user);
        $this->_em->flush($user);
    }
}
