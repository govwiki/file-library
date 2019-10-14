<?php

namespace App\Controller;

use App\Entity\EntityFactory;
use App\Repository\UserRepository;
use App\Service\Authenticator\AuthenticatorException;
use App\Service\Authenticator\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

/**
 * Class LoginAction
 *
 * @package App\Controller
 */
class SecurityController extends AbstractController
{

    const SESSION_REFERER_KEY = '_auth_referer';

    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var Twig
     */
    private $renderer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Helper
     */
    private $session;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var EntityFactory
     */
    private $entityFactory;
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * LoginAction constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface instance.
     * @param Twig $renderer A PhpRenderer instance.
     * @param RouterInterface $router A RouterInterface instance.
     * @param Helper $session A Session instance.
     * @param EntityFactory $entityFactory
     * @param UserRepository $repository
     * @param string $domain Current application domain.
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        Twig $renderer,
        RouterInterface $router,
        Helper $session,
        EntityFactory $entityFactory,
        UserRepository $repository,
        string $domain
    ) {
        $this->authenticator = $authenticator;
        $this->renderer = $renderer;
        $this->router = $router;
        $this->session = $session;
        $this->domain = $domain;
        $this->entityFactory = $entityFactory;
        $this->repository = $repository;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     */
    public function login(Request $request, Response $response): ResponseInterface
    {
        $tmplParams = [ 'error' => '' ];
        $referer = $this->getReferer($request);

        if ($request->isPost()) {
            /** @var array{username: string, password: string} $params */
            $params = $request->getParsedBody();

            if (! isset($params['username'], $params['password'])) {
                return $response->withStatus(400);
            }

            try {
                $this->authenticator->authenticate($params['username'], $params['password']);

                return $response->withRedirect($referer);
            } catch (AuthenticatorException $exception) {
                $tmplParams['error'] = $exception->getMessage();
            }
        }

        return $this->renderer->render($response, 'login.twig', $tmplParams);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     */
    public function logout(Request $request, Response $response): ResponseInterface
    {
        $this->authenticator->logout();

        return $response->withRedirect($this->getReferer($request));
    }

    /**
     * @param Request $request A http request.
     *
     * @return string
     */
    private function getReferer(Request $request): string
    {
        $host = $request->getUri()->getHost();
        /** @var string $referer */
        $referer = $request->getServerParam('HTTP_REFERER');

        if ((\stripos($host, $this->domain) === false) || ($referer === null)) {
            return '/';
        }

        /** @var string $refererPath */
        $refererPath = \parse_url($referer, PHP_URL_PATH);

        if (strcasecmp($refererPath, $this->router->pathFor('login')) !== 0) {
            $this->session->set(self::SESSION_REFERER_KEY, $referer);
        } else {
            /** @var string $referer */
            $referer = $this->session->get(self::SESSION_REFERER_KEY);
        }

        return $referer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function addUser(Request $request, Response $response): ResponseInterface
    {
        $tmplParams = [ 'error' => '', 'message' => ''];

        if ($request->isPost()) {
            /** @var array{username: string, password: string, firstName: string, lastName: string} $params */
            $params = $request->getParsedBody();

            try {
                $user = $this->entityFactory->createUser(
                    $params['username'],
                    $params['password'],
                    $params['firstName'],
                    $params['lastName']
                );

                $this->repository->persist($user);
                $tmplParams['message'] = "User '${params['username']}' is created.";
            } catch (\Exception $exception) {
                $tmplParams['error'] = $exception->getMessage();
            }
        }

        return $this->renderer->render($response, 'add_user.twig', $tmplParams);
    }
}
