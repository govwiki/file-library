<?php

namespace App\Storage\Adapter;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\File\FileRestProxy;
use Psr\Http\Message\StreamInterface;
use Slim\Http\Stream;

/**
 * Class AzureStorageAdapter
 *
 * @package App\Storage\Adapter
 */
class AzureStorageAdapter implements StorageAdapterInterface
{

    /**
     * @var FileRestProxy
     */
    private $client;

    /**
     * @var string
     */
    private $share;

    /**
     * AzureStorageAdapter constructor.
     *
     * @param string $accountName Azure file storage account name.
     * @param string $accountKey  Azure file storage account key.
     * @param string $share       Used file storage share.
     */
    public function __construct(
        string $accountName,
        string $accountKey,
        string $share
    ) {
        $this->client = FileRestProxy::createFileService(\sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;',
            $accountName,
            $accountKey
        ));
        $this->share = $share;
    }
    /**
     * @param string $path Path to created directory.
     *
     * @return void
     *
     * @api
     */
    public function createDirectory(string $path): void
    {
        $parts = \explode('/', $path);
        $currPath = '';

        foreach ($parts as $dir) {
            $currPath .= $dir .'/';
            try {
                $this->client->getDirectoryMetadata($this->share, $currPath);
            } catch (ServiceException $exception) {
                if ($exception->getCode() !== 404) {
                    throw $exception;
                }

                $this->client->createDirectory($this->share, $currPath);
            }
        }
    }

    /**
     * @param string          $path    Path where file should be created.
     * @param StreamInterface $content Stored file content.
     *
     * @return void
     *
     * @api
     */
    public function createFile(string $path, StreamInterface $content)
    {
        $this->createDirectory(\dirname($path));

        $this->client->createFileFromContent(
            $this->share,
            $path,
            $content
        );
    }

    /**
     * Get list of files for specified path.
     *
     * @param string $path
     *
     * @return \Traversable
     * @psalm-return \Traversable<AdapterFile>
     *
     * @api
     */
    public function listFiles(string $path): \Traversable
    {
        $result = $this->client->listDirectoriesAndFiles($this->share, self::normalizePath($path));

        $path = $path === '/' ? '' : $path;

        foreach ($result->getDirectories() as $directory) {
            yield AdapterFile::createDirectory(
                $path .'/'. $directory->getName()
            );
        }

        foreach ($result->getFiles() as $file) {
            yield AdapterFile::createFile(
                $path .'/'. $file->getName(),
                $file->getLength()
            );
        }
    }

    /**
     * @param string $srcPath  Path from which we should move file.
     * @param string $destPath Path to which we should move.
     *
     * @return void
     *
     * @api
     */
    public function move(string $srcPath, string $destPath)
    {
        $srcPath = self::normalizePath($srcPath);
        $destPath = self::normalizePath($destPath);

        $this->client->copyFile($this->share, $destPath, $srcPath);
    }

    /**
     * @param string $path A path to removed file.
     *
     * @return void
     *
     * @api
     */
    public function remove(string $path)
    {
        $path = self::normalizePath($path);

        $this->client->deleteFile($this->share, $path);
    }

    /**
     * @param string $path A path to file.
     *
     * @return StreamInterface
     *
     * @api
     */
    public function read(string $path): StreamInterface
    {
        $path = self::normalizePath($path);
        $result = $this->client->getFile($this->share, $path);

        return new Stream($result->getContentStream());
    }

    /**
     * @param string $path Path to directory.
     *
     * @return string
     */
    private static function normalizePath(string $path): string
    {
        //
        // For some reasons Azure file storage return "The specifed resource name
        // contains invalid characters." error if we try to request directory by
        // path with "/" character at first place as normal people do ...
        //
        return \ltrim($path, '/');
    }
}
