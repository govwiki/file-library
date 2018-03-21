<?php

use Phinx\Seed\AbstractSeed;

/**
 * Class PurgeFixtures
 */
class PurgeFixtures extends AbstractSeed
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
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->table('documents')->truncate();
        $this->table('users')->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}
