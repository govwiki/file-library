<?php

namespace App\Action;

use App\Service\Authenticator\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;

/**
 * Class LogoutAction
 *
 * @package App\Action
 */
class LogoutAction
{

    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * LoginAction constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface instance.
     * @param RouterInterface        $router        A RouterInterface instance.
     */
    public function __construct(
        AuthenticatorInterface $authenticator,
        RouterInterface $router
    ) {
        $this->authenticator = $authenticator;
        $this->router = $router;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $this->authenticator->logout();

        return $response->withRedirect($this->router->pathFor('main'));
    }
}
