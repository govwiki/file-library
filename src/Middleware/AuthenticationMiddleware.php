<?php

namespace App\Middleware;

use App\Service\Authenticator\AuthenticatorInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

/**
 * Class AuthenticationMiddleware
 *
 * @package App\Middleware
 */
class AuthenticationMiddleware
{

    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var Twig
     */
    private $view;

    /**
     * AuthenticationMiddleware constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface
     *                                              instance.
     * @param Twig                   $view          A view service.
     */
    public function __construct(AuthenticatorInterface $authenticator, Twig $view)
    {
        $this->authenticator = $authenticator;
        $this->view = $view;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param callable $next     Next middleware.
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $user = $this->authenticator->refresh();
        if ($user !== null) {
            $request = $request->withAttribute('user', $user);
            $this->view['user'] = $user;
        }

        /** @psalm-suppress MixedReturnStatement */
        return $next($request, $response);
    }
}
