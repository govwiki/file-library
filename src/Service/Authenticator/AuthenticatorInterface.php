<?php

namespace App\Service\Authenticator;

use App\Model\User;

/**
 * Interface AuthenticatorInterface
 *
 * @package App\Service\Authenticator
 */
interface AuthenticatorInterface
{

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
    public function authenticate(string $username, string $password): User;

    /**
     * Refresh user.
     *
     * @return User|null
     */
    public function refresh();

    /**
     * Logout current user.
     *
     * @return void
     */
    public function logout();
}
