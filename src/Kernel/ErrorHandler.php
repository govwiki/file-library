<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

/**
 * Class ErrorAction
 *
 * @package App\Action
 */
class ErrorHandler
{

    /**
     * @var Twig
     */
    private $renderer;

    /**
     * @var string
     */
    private $errorType;

    /**
     * IndexAction constructor.
     *
     * @param Twig   $renderer  A PhpRenderer instance.
     * @param string $errorType Application error type.
     */
    public function __construct(Twig $renderer, string $errorType)
    {
        $this->renderer = $renderer;
        $this->errorType = $errorType;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __invoke(Request $request, Response $response)
    {
        return $this->renderer
            ->render($response, 'error.twig', [ 'type' => $this->errorType ])
            ->withStatus(500);
    }
}
