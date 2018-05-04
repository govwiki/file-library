<?php

namespace App\Service\Storage\Physical;

use GuzzleHttp\Psr7\Stream;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\File\FileRestProxy;
use Psr\Http\Message\StreamInterface;

/**
 * Class AzurePhysicalStorage
 *
 * @package App\Service\Storage\Physical
 */
class AzurePhysicalStorage implements PhysicalStorageInterface
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
     * AzureFileStorage constructor.
     *
     * @param string $accountName Azure file storage account name.
     * @param string $accountKey  Azure file storage account key.
     * @param string $share       Share name which is used for accessing files.
     */
    public function __construct(
        string $accountName,
        string $accountKey,
        string $share
    ) {
        $this->client = FileRestProxy::createFileService(sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
            $accountName,
            $accountKey
        ));
        $this->share = $share;
    }

    /**
     * @param StreamInterface $stream         Source file content as stream.
     * @param string          $destPublicPath Destination file path.
     *
     * @return void
     */
    public function store(StreamInterface $stream, string $destPublicPath)
    {
        $this->insureThatDirectoriesExists(\dirname($destPublicPath));

        $this->client->createFileFromContent(
            $this->share,
            $destPublicPath,
            $stream
        );
    }

    /**
     * Get all files inside specified path.
     *
     * @param string $publicPath Public path to directory.
     *
     * @return PhysicalFileListInterface
     */
    public function listFiles(string $publicPath = '/'): PhysicalFileListInterface
    {
        return new AzurePhysicalFileList($this->client, $this->share, $publicPath);
    }

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPublicPath, string $destPublicPath)
    {
        $this->client->copyFile($this->share, $destPublicPath, $srcPublicPath);
    }

    /**
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath)
    {
        $this->client->deleteFile($this->share, $publicPath);
    }

    /**
     * @param string $publicPath Public path to file.
     *
     * @return StreamInterface
     */
    public function read(string $publicPath): StreamInterface
    {
        $result = $this->client->getFile($this->share, $publicPath);

        return new Stream($result->getContentStream());
    }

    /**
     * Checks that file are exists.
     *
     * @param string $publicPath Public path to file.
     *
     * @return boolean
     */
    public function isExists(string $publicPath): bool
    {
        try {
            $this->client->getFileMetadata($this->share, $publicPath);
        } catch (ServiceException $exception) {
            if ($exception->getCode() === 404) {
                try {
                    $this->client->getDirectoryMetadata($this->share, $publicPath);
                } catch (ServiceException $exception) {
                    if ($exception->getCode() === 404) {
                        return false;
                    }

                    throw $exception;
                }

                return true;
            }

            throw $exception;
        }

        return true;
    }

    /**
     * @param string $path Path to directory which should exists.
     *
     * @return void
     */
    private function insureThatDirectoriesExists(string $path)
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
}
