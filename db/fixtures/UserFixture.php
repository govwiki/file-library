<?php

namespace Fixtures;

use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class UserFixture
 *
 * @package Fixtures
 */
class UserFixture extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager A ObjectManager instance.
     *
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $user = new User('user1', password_hash('user1', \PASSWORD_BCRYPT), 'John', 'Smith');
        $this->addReference('user1', $user);
        $manager->persist($user);

        $user = new User('user2', password_hash('user2', \PASSWORD_BCRYPT), 'Jane', 'Smith');
        $this->addReference('user2', $user);
        $manager->persist($user);

        $user = new User('user3', password_hash('user3', \PASSWORD_BCRYPT), 'John', 'Due');
        $this->addReference('user3', $user);
        $manager->persist($user);

        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder(): int
    {
        return 0;
    }
}
