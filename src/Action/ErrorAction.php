<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

/**
 * Class ErrorAction
 *
 * @package App\Action
 */
class ErrorAction
{

    /**
     * @var PhpRenderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $errorType;

    /**
     * IndexAction constructor.
     *
     * @param PhpRenderer $renderer  A PhpRenderer instance.
     * @param string      $errorType Application error type.
     */
    public function __construct(PhpRenderer $renderer, string $errorType)
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
     */
    public function __invoke(Request $request, Response $response)
    {
        return $this->renderer
            ->render($response, 'error.phtml', [ 'type' => $this->errorType ])
            ->withStatus(500);
    }
}
