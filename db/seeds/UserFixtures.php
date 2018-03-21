<?php

use Phinx\Seed\AbstractSeed;

/**
 * Class UserFixtures
 */
class UserFixtures extends AbstractSeed
{

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     *
     * @return void
     */
    public function run()
    {
        $this->table('users')
            ->insert([
                [
                    'username' => 'test',
                    'password' => password_hash('test', PASSWORD_BCRYPT),
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                ],
                [
                    'username' => 'user1',
                    'password' => password_hash('user1', PASSWORD_BCRYPT),
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                ],
                [
                    'username' => 'user2',
                    'password' => password_hash('user2', PASSWORD_BCRYPT),
                    'first_name' => 'John',
                    'last_name' => 'Due',
                ],
            ])
            ->save();
    }
}
