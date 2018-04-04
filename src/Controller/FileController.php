<?php

namespace App\Controller;

use App\Entity\Directory;
use App\Entity\Document;
use App\Repository\FileRepositoryInterface;
use App\Service\FileStorage\FileStorageInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

/**
 * Class FileController
 *
 * @package App\Controller
 */
class FileController extends AbstractController
{

    /**
     * @var Twig
     */
    private $renderer;

    /**
     * @var FileRepositoryInterface
     */
    private $repository;

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * DocumentController constructor.
     *
     * @param Twig                    $renderer    A template renderer.
     * @param FileRepositoryInterface $repository  A FileRepositoryInterface
     *                                            instance.
     * @param FileStorageInterface    $fileStorage A FileStorageInterface instance.
     */
    public function __construct(
        Twig $renderer,
        FileRepositoryInterface $repository,
        FileStorageInterface $fileStorage
    ) {
        $this->renderer = $renderer;
        $this->repository = $repository;
        $this->fileStorage = $fileStorage;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @throws NotFoundException If can't find specified directory.
     */
    public function index(Request $request, Response $response, array $args): ResponseInterface
    {
        $slug = $this->getArgument($args, 'slug');
        $file = null;
        if ($slug !== null) {
            $file = $this->repository->findBySlug($slug);
        }

        switch (true) {
            case ($file === null) && ($slug === null):
            case $file instanceof Directory:
                return $this->renderer->render($response, 'index.twig', [
                    'currentDir' => $file,
                ]);

            case $file instanceof Document:
                return $response
                    ->withHeader('Content-Type', 'application/force-download')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Type', 'application/download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Disposition', sprintf('attachment; filename="%s.pdf"', $file->getName()))
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'public')
                    ->withBody($this->fileStorage->read($file->getPublicPath()));
        }

        throw new NotFoundException($request, $response);
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
    public function files(Request $request, Response $response, array $args): ResponseInterface
    {
        $slug = $this->getArgument($args, 'slug');
        $publicPath = '/';

        if ($slug !== null) {
            $directory = $this->repository->findBySlug($slug);
            if ($directory === null) {
                return $response->withJson([
                    'errors' => [
                        'title'       => 'Directory not found',
                        'code'        => 'NOT_FOUND',
                        'description' => sprintf('Can\'t find directory by slug "%s"', $slug),
                    ],
                ])
                    ->withStatus(404);
            }

            $publicPath = $directory->getPublicPath();
        }

        $list = $this->fileStorage->listFiles($publicPath)
            ->setLimit($request->getQueryParam('limit'))
            ->setOffset($request->getQueryParam('offset'))
            ->orderBy($request->getQueryParam('order'));

        return $response->withJson([
            'draw' => $request->getQueryParam('draw'),
            'recordsTotal' => $list->count(),
            'recordsFiltered' => $list->count(),
            'data' => iterator_to_array($list),
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
    public function remove(Request $request, Response $response, array $args): ResponseInterface
    {
        $slug = $this->getArgument($args, 'slug');
        $document = $this->repository->findBySlug($slug);

        if ($document === null) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Document not found',
                    'code' => 'NOT_FOUND',
                    'description' => sprintf('Can\'t find document by slug "%s"', $slug),
                ],
            ])
                ->withStatus(404);
        }

        $this->fileStorage->remove($document->getPublicPath());

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
    }
}
