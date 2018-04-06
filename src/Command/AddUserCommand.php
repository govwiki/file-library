<?php

namespace App\Command;

use App\Entity\EntityFactory;
use App\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddUserCommand
 *
 * @package App\Command
 */
class AddUserCommand extends Command
{

    const NAME = 'user:new';

    /**
     * @var EntityFactory
     */
    private $factory;

    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * AddUserCommand constructor.
     *
     * @param EntityFactory           $factory    A EntityFactory instance.
     * @param UserRepositoryInterface $repository A UserRepositoryInterface
     *                                            instance.
     */
    public function __construct(
        EntityFactory $factory,
        UserRepositoryInterface $repository
    ) {
        parent::__construct(self::NAME);

        $this->factory = $factory;
        $this->repository = $repository;
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Add new user')
            ->addArgument('username', InputArgument::REQUIRED, 'Created user username')
            ->addArgument('password', InputArgument::REQUIRED, 'Created user plain password')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Created user first name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Created user last name')
            ->addOption('super', 's', InputOption::VALUE_NONE, 'Is super user, or not?');
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
        $username = trim((string) $input->getArgument('username'));
        $password = trim((string) $input->getArgument('password'));
        $firstName = trim((string) $input->getArgument('firstName'));
        $lastName = trim((string) $input->getArgument('lastName'));
        $superUser = $input->hasOption('super');

        $user = $this->factory->createUser(
            $username,
            $password,
            $firstName,
            $lastName
        );

        if ($superUser) {
            $user->setSuperUser(true);
        }

        $this->repository->persist($user);

        return 0;
    }
}
