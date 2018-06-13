<?php

namespace App\Middleware;

use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\MethodNotAllowed;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class LoggerMiddleware
 *
 * @package App\Middleware
 */
class LoggerMiddleware
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LoggerMiddleware constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
            $this->logger->info(\sprintf(
                'Got new %s request to %s',
                $request->getMethod(),
                (string) $request->getUri()
            ));

            $result = $next($request, $response);
            $this->logger->debug(\sprintf(
                'Request %s %s processing finished',
                $request->getMethod(),
                (string) $request->getUri()
            ));

            return $result;
        } catch (NotFoundException $exception) {
            $this->logger->warning(\sprintf(
                'Try to open not exists page %s %s',
                $request->getMethod(),
                (string)$request->getUri()
            ));
            throw $exception;
        } catch (MethodNotAllowed $exception) {
            $this->logger->warning(\sprintf(
                'Try to execute not allowed method %s for %s',
                $request->getMethod(),
                (string)$request->getUri()
            ));
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logException($exception);
            throw $exception;
        }
    }

    /**
     * @param \Throwable $exception A occurred exception.
     *
     * @return void
     */
    private function logException(\Throwable $exception)
    {
        $this->logger->error(\sprintf(
            'Got exception %s at %s:%d. %s',
            \get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage()
        ));

        if ($exception->getPrevious() !== null) {
            $this->logException($exception->getPrevious());
        }
    }
}
