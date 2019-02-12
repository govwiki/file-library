<?php

namespace App\Controller;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\FileRepositoryInterface;
use App\Service\DocumentMover\DocumentMoverException;
use App\Service\DocumentMover\DocumentMoverService;
use App\Storage\Storage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;
use SlimSession\Helper;

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
     * @var Storage
     */
    private $storage;

    /**
     * @var DocumentMoverService
     */
    private $documentMover;

    /**
     * @var Helper
     */
    private $session;

    /**
     * DocumentController constructor.
     *
     * @param Twig $renderer A template renderer.
     * @param FileRepositoryInterface $repository A FileRepositoryInterface
     *                                               instance.
     * @param Storage $storage A Storage instance.
     * @param DocumentMoverService $documentMover A DocumentMoverService
     *                                               instance.
     * @param Helper $session
     */
    public function __construct(
        Twig $renderer,
        FileRepositoryInterface $repository,
        Storage $storage,
        DocumentMoverService $documentMover,
        Helper $session
    ) {
        $this->renderer = $renderer;
        $this->repository = $repository;
        $this->storage = $storage;
        $this->documentMover = $documentMover;
        $this->session = $session;
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
        $stateFilter = $this->session->get('state_filter');

        $slug = $this->getArgument($args, 'slug');
        $file = null;
        if ($slug !== null) {
            $file = $this->repository->findBySlug($slug);
        }

        $topLevelDirNames = [];
        if ($request->getAttribute('user') instanceof User) {
            $topLevelDirNames = $this->repository->getTopLevelDirNames();
        }

        switch (true) {
            case ($file === null) && ($slug === null):
            case $file instanceof Directory:
                return $this->renderer->render($response, 'index.twig', [
                    'currentDir' => $file,
                    'userJson' => json_encode($request->getAttribute('user')),
                    'topLevelDirNames' => $topLevelDirNames,
                    'defaultOrder' => ($file !== null) && ($file->getParent() === null) ? 'desc' : 'asc',
                    'stateFilter' => $stateFilter
                ]);

            case $file instanceof Document:
                $internalFile = $this->storage->getFile($file->getPublicPath());
                if ($internalFile === null) {
                    throw new NotFoundException($request, $response);
                }

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
                    ->withBody($internalFile->getContent());
        }

        throw new NotFoundException($request, $response);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function files(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->session->set('state_filter', $request->getQueryParam('state'));

        $slug = $this->getArgument($args, 'slug');
        $publicPath = '/';

        if ($slug !== null) {
            $directory = $this->repository->findBySlug($slug);
            if ($directory === null) {
                return $response->withJson([
                    'error' => [
                        'title'       => 'Directory not found',
                        'code'        => 'NOT_FOUND',
                        'description' => \sprintf('Can\'t find directory by slug "%s"', $slug),
                    ],
                ])
                    ->withStatus(404);
            }

            $publicPath = $directory->getPublicPath();
        }

        $search = $request->getQueryParam('search', '');
        $state = $request->getQueryParam('state', '');
        $limit = (int) $request->getQueryParam('limit');

        $directory = $this->storage->getDirectory($publicPath);
        if ($directory === null) {
            throw new NotFoundException($request, $response);
        }

        $list = $directory->getListBuilder()
            ->onlyDocuments($search !== '')
            ->recursive($search !== '')
            ->filterBy($search)
            ->filterByState($state)
            ->showHidden($request->getAttribute('user') instanceof User)
            ->orderBy($request->getQueryParam('order'));

        if ($limit > 0) {
            $list
                ->setLimit($limit)
                ->setOffset($request->getQueryParam('offset'));
        }

        $count = \count($list);

        return $response->withJson([
            'draw' => $request->getQueryParam('draw'),
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => \array_map(function (AbstractFile $file) {
                if ($file instanceof Document) {
                    return [
                        'type' => 'document',
                        'ext' => $file->getExt(),
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'slug' => $file->getSlug(),
                        'createdAt' => $file->getCreatedAt()->format('c'),
                        'parent' => $file->getParent() ? $file->getParent()->getId() : null,
                        'publicPath' => $file->getPublicPath(),
                        'downloadUrl' => $this->storage->generatePublicUrl($file->getPublicPath()),
                        'fileSize' => $file->getFileSize(),
                    ];
                }

                return $file;
            }, \iterator_to_array($list)),
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
        $directory = $this->getFileFromArgs($args);

        if (! $directory->isDirectory()) {
            throw new ApiHttpException(
                'Invalid directory',
                'INVALID_DIRECTORY',
                'Try to upload file not in directory'
            );
        }

        /**
         * @var UploadedFileInterface[] $files
         * @psalm-var Array<string, UploadedFileInterface>
         */
        $files = $request->getUploadedFiles();
        if (! isset($files['file'])) {
            return $response->withJson([
                'error' => [
                    'title' => 'Invalid request',
                    'code' => 'INVALID_REQUEST',
                    'description' => 'File should be uploaded with "file" key',
                ],
            ])
                ->withStatus(404);
        }

        /** @var UploadedFileInterface $file */
        $uploadedFiles = $files['file'];

        foreach ($uploadedFiles as $file) {
            $filePath = $directory->getPublicPath() . '/' . $file->getClientFilename();
            if ($this->storage->isFileExists($filePath)) {
                return $response->withJson([
                    'error' => [
                        'title'       => 'Invalid request',
                        'code'        => 'ALREADY_EXISTS',
                        'description' => \sprintf('File "%s" already exists', $filePath),
                    ],
                ])
                    ->withStatus(400);
            }

            $this->storage->createFile($filePath, $file->getStream());
        }

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
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function remove(Request $request, Response $response, array $args): ResponseInterface
    {
        $document = $this->getFileFromArgs($args);

        $this->storage->remove($document->getPublicPath());

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function butchRemove(Request $request, Response $response): ResponseInterface
    {
        $ids = $request->getParsedBodyParam('ids');

        $documents = $this->repository->findByIds($ids);

        foreach ($documents as $document) {
            $this->storage->remove($document->getPublicPath());
        }

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
     */
    public function move(Request $request, Response $response, array $args): ResponseInterface
    {
        /** @var Document $file */
        $file = $this->getFileFromArgs($args);

        if (! $file->isDocument()) {
            throw new ApiHttpException(
                'Invalid document',
                'INVALID_DOCUMENT',
                'Try to move not a regular document'
            );
        }

        $newTopLevelDirId = $this->getRequestsParameters($request, [ 'topLevelDir' ])['topLevelDir'];
        /** @var Directory|null $newTopLevelDir */
        $newTopLevelDir = $this->repository->findById($newTopLevelDirId);

        if (($newTopLevelDir === null) || ! $newTopLevelDir->isDirectory()) {
            throw new ApiHttpException(
                'Invalid directory',
                'INVALID_DIRECTORY',
                'Try to move document in to unknown directory',
                404
            );
        }

        try {
            $this->documentMover->move($file, $newTopLevelDir);
        } catch (DocumentMoverException $exception) {
            throw new ApiHttpException(
                'Can\'t move',
                'CANT_MOVE',
                $exception->getMessage()
            );
        }

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
    public function rename(Request $request, Response $response, array $args): ResponseInterface
    {
        /** @var Document $file */
        $file = $this->getFileFromArgs($args);

        if (! $file->isDocument()) {
            throw new ApiHttpException(
                'Invalid document',
                'INVALID_DOCUMENT',
                'Try to move not a regular document'
            );
        }

        $newName = $this->getRequestsParameters($request, [ 'name' ])['name'];

        try {
            $this->documentMover->move($file, $file->getTopLevelDir(), $newName);
        } catch (DocumentMoverException $exception) {
            throw new ApiHttpException(
                'Can\'t rename',
                'CANT_RENAME',
                $exception->getMessage()
            );
        }

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
    }

    /**
     * @param array $args Path arguments.
     *
     * @return AbstractFile
     */
    private function getFileFromArgs(array $args): AbstractFile
    {
        $slug = $this->getArgument($args, 'slug');
        $file = $this->repository->findBySlug($slug);

        if ($file === null) {
            throw new ApiHttpException(
                'Document not found',
                'NOT_FOUND',
                sprintf('Can\'t find document by slug "%s"', $slug),
                404
            );
        }

        return $file;
    }

    /**
     * @param Request  $request A http request.
     * @param string[] $params  Expected parameters.
     *
     * @return array
     */
    private function getRequestsParameters(Request $request, array $params): array
    {
        /** @var array<string, mixed> $results */
        $results = [];
        $data = $request->getParsedBody();
        if (! \is_array($data)) {
            throw new ApiHttpException(
                'Invalid request',
                'INVALID_REQUEST',
                sprintf(
                    'Request body should be json object with properties: %s',
                    implode(', ', $params)
                ),
                404
            );
        }

        foreach ($params as $param) {
            if (! isset($data[$param])) {
                throw new ApiHttpException(
                    'Invalid request',
                    'INVALID_REQUEST',
                    sprintf(
                        'Request body should be json object with properties: %s',
                        implode(', ', $params)
                    ),
                    404
                );
            }

            $results[$param] = $data[$param];
        }

        return $results;
    }
}
