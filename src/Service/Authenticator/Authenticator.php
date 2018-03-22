<?php

namespace App\Service\Authenticator;

use App\Model\User;
use App\Repository\UserRepositoryInterface;
use SlimSession\Helper;

/**
 * Class Authenticator
 *
 * @package App\Service\Authenticator
 */
class Authenticator implements AuthenticatorInterface
{

    const USERNAME_KEY = '_username';

    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * @var Helper
     */
    private $session;

    /**
     * Authenticator constructor.
     *
     * @param UserRepositoryInterface $repository A UserRepositoryInterface instance.
     * @param Helper                  $session    A http session.
     */
    public function __construct(UserRepositoryInterface $repository, Helper $session)
    {
        $this->repository = $repository;
        $this->session = $session;
    }

    /**
     * Authenticate user by credentials.
     *
     * @param string $username Username.
     * @param string $password Password.
     *
     * @return User
     *
     * @throws AuthenticatorException If authentication fail.
     */
    public function authenticate(string $username, string $password): User
    {
        $user = $this->repository->findByUsername($username);

        if (($user === null) || ! $user->isValidPassword($password)) {
            throw new AuthenticatorException('Invalid credentials');
        }

        $this->session->set(self::USERNAME_KEY, $user->getUsername());

        return $user;
    }

    /**
     * Refresh user.
     *
     * @return User|null
     */
    public function refresh()
    {
        /** @psalm-suppress MixedAssignment */
        $username = $this->session->get(self::USERNAME_KEY);

        $user = null;
        if (is_string($username)) {
            $user = $this->repository->findByUsername($username);
            if ($user === null) {
                $this->session->delete(self::USERNAME_KEY);
            }
        } else {
            $this->session->delete(self::USERNAME_KEY);
        }

        return $user;
    }

    /**
     * Logout current user.
     *
     * @return void
     */
    public function logout()
    {
        $this->session->clear();
        Helper::destroy();
    }
}
