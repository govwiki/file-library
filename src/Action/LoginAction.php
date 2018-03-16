<?php

namespace App\Action;

use App\Service\Authenticator\AuthenticatorException;
use App\Service\Authenticator\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\PhpRenderer;

/**
 * Class LoginAction
 *
 * @package App\Action
 */
class LoginAction
{

    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var PhpRenderer
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
     * @param PhpRenderer            $renderer      A PhpRenderer instance.
     * @param RouterInterface        $router        A RouterInterface instance.
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        PhpRenderer $renderer,
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
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $tmplParams = [ 'error' => '' ];

        if ($request->isPost()) {
            $params = $request->getParsedBody();

            if (!isset($params['username'], $params['password'])) {
                return $response->withStatus(400);
            }

            try {
                $this->authenticator->authenticate($params['username'], $params['password']);

                return $response->withRedirect($this->router->pathFor('main'));
            } catch (AuthenticatorException $exception) {
                $tmplParams['error'] = $exception->getMessage();
            }
        }

        return $this->renderer->render($response, 'login.phtml', $tmplParams);
    }
}
