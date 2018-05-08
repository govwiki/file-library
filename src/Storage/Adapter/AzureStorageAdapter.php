<?php

namespace App\Storage\Adapter;

use App\Storage\Adapter\File\Directory;
use App\Storage\Adapter\File\File;
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
     * @return Directory
     *
     * @api
     */
    public function createDirectory(string $path): Directory
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

        return new Directory($this, $path);
    }

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path)
    {
        $path = self::normalizeDirectoryPath($path);

        try {
            $this->client->getDirectoryMetadata($this->share, $path);
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            return null;
        }

        return new Directory($this, $path);
    }

    /**
     * @param string          $path    Path where file should be created.
     * @param StreamInterface $content Stored file content.
     *
     * @return File
     *
     * @api
     */
    public function createFile(string $path, StreamInterface $content): File
    {
        $this->createDirectory(\dirname($path));

        $this->client->createFileFromContent(
            $this->share,
            $path,
            $content
        );

        return new File($this, $path, $content->getSize(), $content);
    }

    /**
     * @param string $path Path to required file.
     *
     * @return File|null
     */
    public function getFile(string $path)
    {
        try {
            $metadata = $this->client->getFileMetadata($this->share, $path);
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            return null;
        }

        return new File($this, $path, $metadata->getMetadata()['length']);
    }

    /**
     * Get list of files for specified path.
     *
     * @param string $path
     *
     * @return \Traversable
     * @psalm-return \Traversable<File/File>
     *
     * @api
     */
    public function listFiles(string $path): \Traversable
    {
        $path = self::normalizeDirectoryPath($path);

        $result = $this->client->listDirectoriesAndFiles($this->share, $path);

        foreach ($result->getDirectories() as $directory) {
            yield new Directory(
                $this,
                $path .'/'. $directory->getName()
            );
        }

        foreach ($result->getFiles() as $file) {
            yield new File(
                $this,
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
        $path = self::normalizeDirectoryPath($path);

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
        $result = $this->client->getFile($this->share, $path);

        return new Stream($result->getContentStream());
    }

    /**
     * @param string $path Path to directory.
     *
     * @return string
     */
    private static function normalizeDirectoryPath(string $path): string
    {
        //
        // For some reasons Azure file storage return "The specifed resource name
        // contains invalid characters." error if we try to request root directory
        // by "/" as normal people do.
        //
        if ($path === '/') {
            $path = '.';
        }

        return $path;
    }
}
