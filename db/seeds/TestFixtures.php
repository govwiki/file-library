<?php


use Phinx\Seed\AbstractSeed;

/**
 * Class TestFixtures
 */
class TestFixtures extends AbstractSeed
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
            ])
            ->save();
    }
}
