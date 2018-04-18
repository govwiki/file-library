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

    const TOP_DIRS = [
        'Non Profit',
        'School Districts',
    ];

    const STATE = [
        'CA',
        'MI',
        'OH',
        'AI',
        'TX',
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

    /**
     * @param Directory $parent Parent directory.
     *
     * @return Directory[]
     */
    private function generateYearsDirs(Directory $parent): array
    {
        $faker = $this->getFaker();
        return \array_map(function (int $year) use ($parent): Directory {
            $name = (string) $year;
            $publicPath = $parent->getPublicPath() .'/'. $name;

            return new Directory(
                $name,
                $publicPath,
                $this->slugify($publicPath),
                $parent
            );
        }, $faker->randomElements(\range(2014, 2018), $faker->numberBetween(2, 4)));
    }

    /**
     * @param Directory $parent Parent directory.
     *
     * @return array
     */
    private function generateDocuments(Directory $parent): array
    {
        $faker = $this->getFaker();
        $count = $faker->numberBetween(10, 40);
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
