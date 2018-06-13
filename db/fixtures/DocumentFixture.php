<?php

namespace Fixtures;

use App\Entity\Directory;
use App\Entity\Document;
use Cocur\Slugify\Slugify;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

/**
 * Class DocumentFixture
 *
 * @package Fixtures
 */
class DocumentFixture extends AbstractFixture implements OrderedFixtureInterface
{

    const MIN_COUNT = 10000;
    const MAX_COUNT = 15000;

    const MIN_YEAR = 2000;
    const MAX_YEAR = 2018;

    const TOP_DIRS = [
        'Community College District',
        'General Purpose',
        'Non-Profit',
        'Public Higher Education',
        'School District',
        'Special District',
        'States',
        'Unclassified',
    ];

    const STATE = [
        'AL',
        'AK',
        'AS',
        'AZ',
        'AR',
        'CA',
        'CO',
        'CT',
        'DE',
        'DC',
        'FM',
        'FL',
        'GA',
        'GU',
        'HI',
        'ID',
        'IL',
        'IN',
        'IA',
        'KS',
        'KY',
        'LA',
        'ME',
        'MD',
        'MA',
        'MI',
        'MN',
        'MS',
        'MO',
        'MT',
        'NE',
        'NV',
        'NH',
        'NJ',
        'NM',
        'NY',
        'NC',
        'ND',
        'MP',
        'OH',
        'OK',
        'OR',
        'PA',
        'PR',
        'RI',
        'SC',
        'SD',
        'TN',
        'TX',
        'UT',
        'VT',
        'VI',
        'VA',
        'WA',
        'WV',
        'WI',
        'WY',
    ];

    const BASEFILE_SIZE = 1024 * 1024;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var Slugify|null
     */
    private $slugifier;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager A ObjectManager instance.
     *
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::TOP_DIRS as $topDir) {
            $publicPath = '/'. $topDir;
            $directory = new Directory($topDir, $publicPath, $this->slugify($publicPath));

            $yearsDirs = $this->generateYearsDirs($directory);

            foreach ($yearsDirs as $dir) {
                $documents = $this->generateDocuments($dir);

                foreach ($documents as $document) {
                    $manager->persist($document);
                }
                $manager->persist($dir);
            }

            $manager->persist($directory);
            $manager->flush();
        }
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

    /**
     * @param Directory $parent Parent directory.
     *
     * @return Directory[]
     */
    private function generateYearsDirs(Directory $parent): array
    {
        $faker = $this->getFaker();

        if (self::MIN_YEAR >= self::MAX_YEAR - 1) {
            throw new \LogicException('self::MIN_YEAR should be less than self::MAX_YEAR - 1!');
        }

        $minYear = $faker->numberBetween(self::MIN_YEAR, self::MAX_YEAR - 1);
        $maxYear = $faker->numberBetween($minYear, self::MAX_YEAR);
        $range = \range($minYear, $maxYear);

        return \array_map(function (int $year) use ($parent): Directory {
            $name = (string) $year;
            $publicPath = $parent->getPublicPath() .'/'. $name;

            return new Directory(
                $name,
                $publicPath,
                $this->slugify($publicPath),
                $parent
            );
        }, $faker->randomElements($range, $faker->numberBetween(1, \count($range))));
    }

    /**
     * @param Directory $parent Parent directory.
     *
     * @return array
     */
    private function generateDocuments(Directory $parent): array
    {
        $faker = $this->getFaker();
        $count = $faker->numberBetween(self::MIN_COUNT, self::MAX_COUNT);
        $documents = [];

        for ($i = 0; $i < $count; ++$i) {
            $name = \sprintf(
                '%s %s %s.pdf',
                $faker->randomElement(self::STATE),
                $faker->words($faker->numberBetween(2, 5), true),
                $parent->getName()
            );
            $publicPath = $parent->getPublicPath() . '/'. $name;

            $documents[] = new Document(
                $name,
                'pdf',
                $publicPath,
                $this->slugify($publicPath),
                $faker->numberBetween(1, 10) * self::BASEFILE_SIZE,
                $parent
            );
        }

        return $documents;
    }

    /**
     * @param string $str Some string which should be slugged.
     *
     * @return string
     */
    private function slugify(string $str): string
    {
        if ($this->slugifier === null) {
            $this->slugifier = new Slugify();
        }

        return $this->slugifier->slugify($str);
    }

    /**
     * @return Generator
     */
    private function getFaker(): Generator
    {
        if ($this->faker === null) {
            $this->faker = Factory::create();
        }

        return $this->faker;
    }
}
