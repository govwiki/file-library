<?php

namespace App\Middleware;

use App\Entity\User;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AuthorizationCheckMiddleware
 *
 * @package App\Middleware
 */
class AuthorizationCheckMiddleware
{

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param callable $next     Next middleware.
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $user = $request->getAttribute('user');
        if (! $user instanceof User) {
            return $response
                ->withStatus(403);
        }

        /** @psalm-suppress MixedReturnStatement */
        return $next($request, $response);
    }
}
