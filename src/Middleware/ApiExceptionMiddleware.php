<?php

namespace App\Middleware;

use App\Controller\ApiHttpException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ApiExceptionMiddleware
 *
 * @package App\Middleware
 */
class ApiExceptionMiddleware
{

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param callable $next     Next middleware.
     *
     * @return Response
     *
     * @psalm-suppress MixedReturnStatement
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        try {
            return $next($request, $response);
        } catch (ApiHttpException $exception) {
            return $response->withJson([
                'title' => $exception->getTitle(),
                'code' => $exception->getErrorCode(),
                'description' => $exception->getDescription(),
            ], $exception->getStatus());
        }
    }
}
