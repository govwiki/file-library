<?php

namespace Fixtures;

use App\Entity\DocumentFactory;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Class DocumentFixture
 *
 * @package Fixtures
 */
class DocumentFixture extends AbstractFixture implements OrderedFixtureInterface
{

    const DOCUMENT_COUNT = 1000;

    const STATES = [
        'AL',
        'CA',
        'IA',
        'KS',
        'MA',
        'NE',
        'OH',
        'PA',
        'TX',
        'WA',
    ];

    const TYPES = [
        'Community College District',
        'Defaulting and Bankrupt Governments',
        'General Purpose',
        'Non-Profit',
        'Public Higher Education',
        'School District',
        'Special District',
        'States',
        'Unclassified',
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager A ObjectManager instance.
     *
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        /** @var User[] $users */
        $users = [
            $this->getReference('user1'),
            $this->getReference('user2'),
            $this->getReference('user3'),
            null,
        ];

        $factory = new DocumentFactory();

        for ($i = 0; $i < self::DOCUMENT_COUNT; $i++) {
            $state = $faker->randomElement(self::STATES);
            $year = $faker->numberBetween(2014, 2018);
            $name = sprintf(
                '%s %s %d',
                $state,
                $faker->words($faker->numberBetween(2, 4), true),
                $year
            );
            $type = $faker->randomElement(self::TYPES);
            $path = '/some/path/'. $type .'/'. $state .'/'. $year .'/'. $name . '.pdf';

            $document = $factory->createDocument(
                $name,
                $type,
                $state,
                $year,
                $path,
                $faker->randomFloat(1, 10, 15)  * 1024 * 1024
            );

            $manager->persist($document->setUploadedBy($faker->randomElement($users)));
        }

        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder(): int
    {
        return 1;
    }
}
