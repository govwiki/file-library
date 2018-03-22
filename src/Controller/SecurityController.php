<?php

namespace App\Controller;

use App\Service\Authenticator\AuthenticatorException;
use App\Service\Authenticator\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;

/**
 * Class LoginAction
 *
 * @package App\Controller
 */
class SecurityController
{

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
     * LoginAction constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface instance.
     * @param Twig                   $renderer      A PhpRenderer instance.
     * @param RouterInterface        $router        A RouterInterface instance.
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        Twig $renderer,
        RouterInterface $router
    ) {
        $this->authenticator = $authenticator;
        $this->renderer = $renderer;
        $this->router = $router;
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

        if ($request->isPost()) {
            /** @var array{username: string, password: string} $params */
            $params = $request->getParsedBody();

            if (! isset($params['username'], $params['password'])) {
                return $response->withStatus(400);
            }

            try {
                $this->authenticator->authenticate($params['username'], $params['password']);

                return $response->withRedirect($this->router->pathFor('types'));
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function logout(Request $request, Response $response): ResponseInterface
    {
        $this->authenticator->logout();

        return $response->withRedirect($this->router->pathFor('types'));
    }
}
