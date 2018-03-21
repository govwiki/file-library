<?php

namespace App\Repository\PDO;

use App\Model\User;
use App\Repository\UserRepositoryInterface;

/**
 * Class UserPDORepository
 *
 * @package App\Repository\PDO
 */
class UserPDORepository extends AbstractPDORepository implements UserRepositoryInterface
{

    /**
     * @param string $username Username.
     *
     * @return User|null
     */
    public function findByUsername(string $username)
    {
        $stmt = $this->execute('SELECT * FROM `users` WHERE username = :username', [
            'username' => $username,
        ]);

        /** @var array|boolean $data */
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! is_array($data)) {
            return null;
        }

        return $this->hydrate(User::class, $data);
    }
}
