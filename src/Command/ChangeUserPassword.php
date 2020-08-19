<?php

namespace App\Command;

use App\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

/**
 * Class AddUserCommand
 *
 * @package App\Command
 */
class ChangeUserPassword extends Command
{
    const NAME = 'user:change:password';

    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * @param UserRepositoryInterface $repository
     */
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct(self::NAME);

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
            ->setDescription('Change user password')
            ->addArgument('username', InputArgument::REQUIRED, 'Created user username');
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
        $symfonyStyle = new SymfonyStyle($input, $output);

        $username = trim((string) $input->getArgument('username'));
        $user     = $this->repository->findByUsername($username);

        if (null === $user) {
            $symfonyStyle->error('User not found.');
            return 0;
        }

        $helper           = $this->getHelper('question');
        $questionPassword = new Question('Enter a new password: ');

        $questionPassword->setNormalizer(function ($value) {
            return trim($value);
        });

        $questionPassword->setValidator(function ($answer) use ($symfonyStyle){
            if (! is_string($answer) || empty($answer)) {
                $symfonyStyle->error('The password cannot be empty');
            }

            return $answer;
        });

        $user->changePassword(
            $helper->ask($input, $output, $questionPassword)
        );

        $this->repository->persist($user);

        $symfonyStyle->success('Password has been updated.');
        return 0;
    }
}
