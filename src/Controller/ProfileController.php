<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use Assert\Assert;
use Assert\LazyAssertionException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;

/**
 * Class ProfileController
 *
 * @package App\Controller
 */
class ProfileController extends AbstractController
{

    /**
     * @var Twig
     */
    private $renderer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UserRepositoryInterface
     */
    private $repository;

    /**
     * ProfileController constructor.
     *
     * @param Twig                    $renderer   A Twig instance.
     * @param RouterInterface         $router     A RouterInterface instance.
     * @param UserRepositoryInterface $repository A UserRepositoryInterface
     *                                            instance.
     */
    public function __construct(
        Twig $renderer,
        RouterInterface $router,
        UserRepositoryInterface $repository
    ) {
        $this->renderer = $renderer;
        $this->router = $router;
        $this->repository = $repository;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        $tmplParams = [];
        $user = $request->getAttribute('user');

        if (! $user instanceof User) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        if ($request->isPost()) {
            /** @var array{username: string, password: string} $params */
            $params = $request->getParsedBody();

            try {
                Assert::lazy()
                    ->that($params, '')
                    ->keyExists('first_name', 'First name should not be empty')
                    ->keyExists('last_name', 'Last name should not be empty')
                    ->keyExists('password', 'Password should not be empty')
                    ->verifyNow();

                $firstName = \trim($params[ 'first_name' ]);
                $lastName = \trim($params[ 'last_name' ]);
                $password = \trim($params[ 'password' ]);

                Assert::lazy()
                    ->that($firstName, 'firstName')
                        ->string()
                        ->maxLength(255)
                    ->that($lastName, 'lastName')
                        ->string()
                        ->maxLength(255)
                    ->that($password, 'password')
                        ->satisfy(function (string $password = null) {
                            $len = \strlen($password);

                            return ($password === '') || (($len >= 6) && ($len <= 255));
                        }, 'Password should be at least 6 characters long and less then 255')
                    ->verifyNow();

                $user
                    ->setFirstName($firstName)
                    ->setLastName($lastName);

                if ($password !== '') {
                    $user->changePassword($password);
                }

                $this->repository->persist($user);
            } catch (LazyAssertionException $exception) {
                $tmplParams['error'] = $exception->getErrorExceptions();
            }
        }

        return $this->renderer->render($response, 'profile.twig', $tmplParams);
    }
}
