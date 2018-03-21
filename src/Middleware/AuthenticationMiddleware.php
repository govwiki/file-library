<?php

namespace App\Middleware;

use App\Service\Authenticator\AuthenticatorInterface;
use Slim\Http\Request;
use Slim\Http\Response;

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
     * AuthenticationMiddleware constructor.
     *
     * @param AuthenticatorInterface $authenticator A AuthenticatorInterface
     *                                              instance.
     */
    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
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
        }

        /** @psalm-suppress MixedReturnStatement */
        return $next($request, $response);
    }
}
