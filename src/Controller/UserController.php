<?php declare(strict_types = 1);

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
 * Class UserController
 *
 * @package App\Controller
 */
class UserController extends AbstractController
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
        $user = $request->getAttribute('user');

        if (! $user instanceof User) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        $page  = (int) $request->getQueryParam('page', 1);
        $limit = (int) $request->getQueryParam('limit', 20);

        if (! $user instanceof User) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        $count = $this->repository->getCount();

        return $this->renderer->render($response, 'user/list.twig', [
            'pagination'    => [
                'needed'        => $count > $limit,
                'count'         => $count,
                'page'          => $page,
                'lastPage'      => (ceil($count / $limit) === false ? 1 : ceil($count / $limit)),
                'limit'         => $limit,
            ],
            'users' => $this->repository->findByPage($page, $limit)
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function edit(Request $request, Response $response, array $args):ResponseInterface
    {
        $errors   = [];
        $authUser = $request->getAttribute('user');

        if (! $authUser instanceof User || ! $authUser->isSuperUser()) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        $username = $this->getArgument($args, 'username');
        $user     = $this->repository->findByUsername($username);

        if (! $user instanceof User ) {
            return $response->withRedirect($this->router->pathFor('user-list'));
        }

        if ($request->isPost()) {
            /** @var array{username: string, password: string|null, firstName: string, lastName: string, admin: string} $params */
            $params = $request->getParsedBody();

            try {
                Assert::lazy()
                    ->that($params, '')
                    ->keyExists('username', 'User name should not be empty')
                    ->keyExists('first_name', 'First name should not be empty')
                    ->keyExists('last_name', 'Last name should not be empty')
                    ->keyExists('password', 'Password should not be empty')
                    ->verifyNow();

                $dataUsername  = \trim($params[ 'username' ]);
                $dataFirstName = \trim($params[ 'first_name' ]);
                $dataLastName  = \trim($params[ 'last_name' ]);
                $dataPassword  = \trim($params[ 'password' ]);
                $dataAdmin     = array_key_exists('admin', $params) && (boolean) $params['admin'];

                Assert::lazy()
                    ->that($dataUsername, 'username')
                    ->string()
                    ->maxLength(255)
                    ->that($dataFirstName, 'firstName')
                    ->string()
                    ->maxLength(255)
                    ->that($dataLastName, 'lastName')
                    ->string()
                    ->maxLength(255)
                    ->that($dataPassword, 'password')
                    ->satisfy(function (string $password = null) {
                        $len = \strlen($password);

                        return ($password === '') || (($len >= 6) && ($len <= 255));
                    }, 'Password should be at least 6 characters long and less then 255')
                    ->that($dataAdmin, 'admin')
                    ->boolean()
                    ->verifyNow();

                if ($dataUsername !== $username) {
                    $existUser = $this->repository->findByUsername($dataUsername);

                    if ($existUser instanceof User) {
                        $message = "User with username: $dataUsername already exist.";
                        throw new LazyAssertionException($message, [
                            new \InvalidArgumentException($message)
                        ]);
                    }
                }

                $user
                    ->setUsername($dataUsername)
                    ->setFirstName($dataFirstName)
                    ->setLastName($dataLastName)
                    ->setSuperUser($dataAdmin);

                if ($dataPassword !== '') {
                    $user->changePassword($dataPassword);
                }

                $this->repository->persist($user);

                return $response->withRedirect($this->router->pathFor('user-edit', ['username' => $user->getUsername()]));
            } catch (LazyAssertionException $exception) {
                $errors = $exception->getErrorExceptions();
            }
        }

        return $this->renderer->render($response, 'user/edit.twig', [
            'user'  => $user,
            'error' => $errors
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function changePassword(Request $request, Response $response, array $args):ResponseInterface
    {
        $errors   = [];
        $authUser = $request->getAttribute('user');

        if (! $authUser instanceof User || ! $authUser->isSuperUser()) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        $username = $this->getArgument($args, 'username');
        $user     = $this->repository->findByUsername($username);

        if (! $user instanceof User ) {
            return $response->withRedirect($this->router->pathFor('user-list'));
        }

        if ($request->isPost()) {
            /** @var array{ password: string } $params */
            $params = $request->getParsedBody();

            try {
                Assert::lazy()
                    ->that($params, '')
                    ->keyExists('password', 'Password should not be empty')
                    ->verifyNow();

                $dataPassword = \trim($params[ 'password' ]);

                Assert::lazy()
                    ->that($dataPassword, 'password')
                    ->satisfy(function (string $password = null) {
                        $len = \strlen($password);

                        return ($password === '') || (($len >= 6) && ($len <= 255));
                    }, 'Password should be at least 6 characters long and less then 255')
                    ->verifyNow();

                $user->changePassword($dataPassword);

                $this->repository->persist($user);

                return $response->withRedirect($this->router->pathFor('user-list'));
            } catch (LazyAssertionException $exception) {
                $errors = $exception->getErrorExceptions();
            }
        }

        return $this->renderer->render($response, 'user/change-password.twig', [
            'error' => $errors
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function delete(Request $request, Response $response, array $args): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (! $user instanceof User || ! $user->isSuperUser()) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        if ($request->isDelete()) {
            $username   = $this->getArgument($args, 'username');
            $userDelete = $this->repository->findByUsername($username);

            if (null === $userDelete || $userDelete->getUsername() === $user->getUsername()) {
                return $response->withRedirect($this->router->pathFor('user-list'));
            }

            $this->repository->delete($userDelete);
        }

        return $response->withRedirect($this->router->pathFor('user-list'));
    }
}
