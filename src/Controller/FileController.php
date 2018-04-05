<?php

namespace App\Controller;

use App\Entity\Directory;
use App\Entity\Document;
use App\Repository\FileRepositoryInterface;
use App\Service\FileStorage\FileStorageException;
use App\Service\FileStorage\FileStorageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
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
                    ->withHeader('Content-Disposition', sprintf(
                        'attachment; filename="%s.%s"',
                        $file->getName(),
                        $file->getExt()
                    ))
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
     */
    public function upload(Request $request, Response $response, array $args): ResponseInterface
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

        /**
         * @var UploadedFileInterface[] $files
         * @psalm-var Array<string, UploadedFileInterface>
         */
        $files = $request->getUploadedFiles();
        if (! isset($files['file'])) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Invalid request',
                    'code' => 'INVALID_REQUEST',
                    'description' => 'File should be uploaded with "file" key',
                ],
            ])
                ->withStatus(404);
        }

        /** @var UploadedFileInterface $file */
        $file = $files['file'];

        $this->fileStorage->store($file->getStream(), $document->getPublicPath() . '/'. $file->getClientFilename());

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
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

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function update(Request $request, Response $response, array $args): ResponseInterface
    {
        $slug = $this->getArgument($args, 'slug');
        /** @var array{publicPath: string} $data */
        $data = $request->getParsedBody();

        if (! \is_array($data) || ! isset($data['publicPath'])) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Invalid request',
                    'code' => 'INVALID_REQUEST',
                    'description' => 'Request body should be json object with "publicPath" property',
                ],
            ])
                ->withStatus(404);
        }
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

        $publicPath = $data['publicPath'];
        try {
            $this->fileStorage->move($document->getPublicPath(), $publicPath);
        } catch (FileStorageException $exception) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Can\'t move',
                    'code' => 'CANT_MOVE',
                    'description' => sprintf('Can\'t move file "%s" to "%s"', $slug, $publicPath),
                ],
            ])
                ->withStatus(404);
        }

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
    }
}
