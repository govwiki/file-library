<?php

use Phinx\Seed\AbstractSeed;
use Faker\Factory;
use Faker\Generator;
use Cocur\Slugify\Slugify;

/**
 * Class DocumentFixtures
 */
class DocumentFixtures extends AbstractSeed
{

    const DOCUMENT_COUNT = 400;

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
     * @var Slugify
     */
    private $slugify;

    /**
     * @var Generator
     */
    private $faker;

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
        $data = [];
        /** @var \PDOStatement $stmt */
        $stmt = $this->query('SELECT id FROM users');
        $ids = array_map('current', $stmt->fetchAll(\PDO::FETCH_ASSOC));

        for ($i = 0; $i < self::DOCUMENT_COUNT; $i++) {
            $state = $this->faker->randomElement(self::STATES);
            $year = $this->faker->numberBetween(2014, 2018);
            $name = sprintf(
                '%s %s %d',
                $state,
                $this->faker->words($this->faker->numberBetween(2, 4), true),
                $year
            );
            $type = $this->faker->randomElement(self::TYPES);
            $path = '/some/path/'. $type .'/'. $state .'/'. $year .'/'. $name . '.pdf';

            $data[] = [
                'slug' => $this->slugify->slugify($type .'/'. $state .'/'. $year .'/'. $name),
                'name' => $name,
                'state' => $state,
                'type' => $type,
                'year' => $year,
                'path' => $path,
                'file_size' => $this->faker->randomFloat(1, 10, 15)  * 1024 * 1024,
                'uploaded_at' => $this->faker->dateTimeBetween('-2 month')->format('Y-m-d H:i:s'),
                'uploaded_by_id' => $this->faker->randomElement($ids),
            ];
        }

        $this->table('documents')
            ->insert($data)
            ->save();
    }

    /**
     * Initialize method.
     *
     * @return void
     */
    protected function init()
    {
        $this->slugify = new Slugify();
        $this->faker = Factory::create();
    }
}
