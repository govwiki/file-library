<?php

namespace App\Controller;

use App\Entity\DocumentFactory;
use App\Repository\DocumentRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class IndexController
 *
 * @package App\Controller
 */
class DocumentApiController extends AbstractController
{

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @var DocumentFactory
     */
    private $factory;

    /**
     * DocumentController constructor.
     *
     * @param DocumentRepositoryInterface $repository A DocumentRepositoryInterface
     *                                                instance.
     * @param DocumentFactory             $factory    A DocumentFactory instance.
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        DocumentFactory $factory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function documents(Request $request, Response $response, array $args): ResponseInterface
    {
        $type = $this->getArgument($args, 'type');
        $state = $this->getArgument($args, 'state');
        $year = $this->getArgument($args, 'year');

        /** @var array{draw: string, order: string[], offset: int, limit: int} $params */
        $params = $request->getQueryParams();

        $collection = $this->repository->getDocuments($type, $state, $year, $params['order'], $params['offset'], $params['limit']);

        return $response->withJson([
            'draw' => $params['draw'],
            'recordsTotal' => $collection->getTotalCount(),
            'recordsFiltered' => $collection->getFilteredCount(),
            'data' => $collection->getDocuments(),
        ]);
    }

    /**
     * @param Request  $request  A http request.
     * @param Response $response A http response.
     * @param array    $args     Path arguments.
     *
     * @return ResponseInterface
     */
    public function rename(Request $request, Response $response, array $args): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (! is_array($params) || ! isset($params['name'])) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Invalid request',
                    'code' => 'INVALID_REQUEST',
                    'description' => 'Required field \'name\' is not provided',
                ],
            ]);
        }

        $slug = $this->getArgument($args, 'slug');

        $document = $this->repository->getBySlug($slug);
        if ($document === null) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Document not found',
                    'code' => 'NOT_FOUND',
                    'description' => sprintf('Can\'t find document by slug "%s"', $slug),
                ],
            ]);
        }

        $document->setName($params['name']);
        $this->repository->save($document);

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

        $document = $this->repository->getBySlug($slug);
        if ($document === null) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Document not found',
                    'code' => 'NOT_FOUND',
                    'description' => sprintf('Can\'t find document by slug "%s"', $slug),
                ],
            ]);
        }

        $this->repository->remove($document);

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
    public function upload(Request $request, Response $response, array $args): ResponseInterface
    {
        $type = $this->getArgument($args, 'type');
        $state = $this->getArgument($args, 'state');
        $year = $this->getArgument($args, 'year');

        /** @var array{document: UploadedFileInterface} $files */
        $files = $request->getUploadedFiles();

        if (! isset($files['document'])) {
            return $response->withJson([
                'errors' => [
                    'title' => 'Invalid request',
                    'code' => 'INVALID_REQUEST',
                    'description' => 'File with key "document" not uploaded',
                ],
            ]);
        }

        /** @var UploadedFileInterface $file */
        $file = $files['document'];

        $document = $this->factory->createDocument(
            preg_replace('/\.pdf$/', '', $file->getClientFilename()),
            $type,
            $state,
            $year,
            '/some/'. $file->getClientFilename(),
            $file->getSize() ?? 0
        );

        $document->setUploadedBy($request->getAttribute('user'));
        $this->repository->save($document);

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(204);
    }
}
