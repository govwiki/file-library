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
        /** @var User|null $user */
        $user = $request->getAttribute('user');

        if (! $user instanceof User || !$user->isSuperUser()) {
            return $response->withJson([
                'error' => [
                    'title' => 'Authorization fail',
                    'code' => 'AUTHORIZATION_FAIL',
                ],
            ])
                ->withStatus(403);
        }

        /** @psalm-suppress MixedReturnStatement */
        return $next($request, $response);
    }
}
