<?php

namespace App\Controller;

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
     * LoginAction constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface instance.
     * @param Twig                   $renderer      A PhpRenderer instance.
     * @param RouterInterface        $router        A RouterInterface instance.
     * @param Helper                 $session       A Session instance.
     * @param string                 $domain        Current application domain.
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        Twig $renderer,
        RouterInterface $router,
        Helper $session,
        string $domain
    ) {
        $this->authenticator = $authenticator;
        $this->renderer = $renderer;
        $this->router = $router;
        $this->session = $session;
        $this->domain = $domain;
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
            return $this->router->pathFor('files');
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
}
