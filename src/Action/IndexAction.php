<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

/**
 * Class IndexAction
 *
 * @package App\Action
 */
class IndexAction
{

    /**
     * @var PhpRenderer
     */
    private $renderer;

    /**
     * IndexAction constructor.
     *
     * @param PhpRenderer $renderer A PhpRenderer instance.
     */
    public function __construct(PhpRenderer $renderer)
    {
        $this->renderer = $renderer;
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
        return $this->renderer->render($response, 'index.phtml');
    }
}
