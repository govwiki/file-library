<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
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
    public function delete(Request $request, Response $response, array $args): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (! $user instanceof User) {
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
