<?php

namespace App\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FixtureCommand
 *
 * @package App\Command
 */
class FixtureCommand extends Command
{

    const BUCKET_SIZE = 25;
    const NAME = 'fixtures:load';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $fixturesPath;

    /**
     * FixtureCommand constructor.
     *
     * @param EntityManagerInterface $em           A EntityManagerInterface
     *                                             instance.
     * @param string                 $fixturesPath Path to fixtures directory.
     */
    public function __construct(
        EntityManagerInterface $em,
        string $fixturesPath
    ) {
        parent::__construct(self::NAME);

        $this->em = $em;
        $this->fixturesPath = $fixturesPath;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Load data fixtures.');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return integer
     *
     * @see setCode()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \ini_set('memory_limit', '1G');

        $conn = $this->em->getConnection();
        $loader = new Loader();

        $loader->loadFromDirectory($this->fixturesPath);
        $output->writeln('<info>Load fixtures</info>');

        try {
            $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
            $executor = new ORMExecutor($this->em, new ORMPurger());
            $executor->setLogger(function (string $message) use ($output) {
                $output->writeln("\t> ". $message);
            });
            $executor->execute($loader->getFixtures());
        } finally {
            $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        return 0;
    }
}
