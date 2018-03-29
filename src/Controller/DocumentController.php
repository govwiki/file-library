<?php

namespace App\Controller;

use App\Repository\DocumentRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use Slim\Views\Twig;

/**
 * Class IndexController
 *
 * @package App\Controller
 */
class DocumentController extends AbstractController
{

    /**
     * @var Twig
     */
    private $renderer;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * DocumentController constructor.
     *
     * @param Twig                        $renderer   A template renderer.
     * @param DocumentRepositoryInterface $repository A DocumentRepositoryInterface
     *                                                instance.
     */
    public function __construct(
        Twig $renderer,
        DocumentRepositoryInterface $repository
    ) {
        $this->renderer = $renderer;
        $this->repository = $repository;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function types(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, 'types.twig', [
            'types' => $this->repository->getTypes(),
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function states(Request $request, Response $response, array $args): ResponseInterface
    {
        $type = $this->getArgument($args, 'type');

        return $this->renderer->render($response, 'states.twig', [
            'type' => $type,
            'states' => $this->repository->getStates($type),
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function years(Request $request, Response $response, array $args): ResponseInterface
    {
        $type = $this->getArgument($args, 'type');
        $state = $this->getArgument($args, 'state');

        return $this->renderer->render($response, 'years.twig', [
            'type' => $type,
            'state' => $state,
            'years' => $this->repository->getYears($type, $state),
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function documents(Request $request, Response $response, array $args): ResponseInterface
    {
        $type = $this->getArgument($args, 'type');
        $state = $this->getArgument($args, 'state');
        $year = $this->getArgument($args, 'year');

        return $this->renderer->render($response, 'documents.twig', [
            'type' => $type,
            'state' => $state,
            'year' => $year,
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @throws NotFoundException Requested document file not found or not readable.
     */
    public function document(Request $request, Response $response, array $args): ResponseInterface
    {
        $slug = $this->getArgument($args, 'slug');
        $document = $this->repository->getBySlug($slug);

        if (($document === null) || ! is_readable($document->getPath())) {
            throw new NotFoundException($request, $response);
        }

        $file = fopen($document->getPath(), 'rb');
        if (! is_resource($file)) {
            throw new \RuntimeException(sprintf(
                'Can\'t open file \'%s\'',
                $document->getPath()
            ));
        }

        return $response
            ->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Type', 'application/download')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s.pdf"', $document->getName()))
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->withBody(new Stream($file));
    }
}
