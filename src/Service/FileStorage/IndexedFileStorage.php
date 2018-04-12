<?php

namespace App\Service\FileStorage;

use App\Service\FileStorage\FileList\FileListInterface;
use App\Service\FileStorage\Index\FileStorageIndexInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class IndexedFileStorage
 *
 * @package App\Service\FileStorage
 */
class IndexedFileStorage implements FileStorageInterface
{

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * @var FileStorageIndexInterface
     */
    private $fileStorageIndex;

    /**
     * IndexedFileStorage constructor.
     *
     * @param FileStorageInterface      $fileStorage      A wrapped file storage.
     * @param FileStorageIndexInterface $fileStorageIndex Used index adapter.
     */
    public function __construct(
        FileStorageInterface $fileStorage,
        FileStorageIndexInterface $fileStorageIndex
    ) {
        $this->fileStorage = $fileStorage;
        $this->fileStorageIndex = $fileStorageIndex;
    }

    /**
     * @param StreamInterface $stream         Source file content as stream.
     * @param string          $destPublicPath Destination file path.
     *
     * @return void
     */
    public function store(StreamInterface $stream, string $destPublicPath)
    {
        $this->fileStorage->store($stream, $destPublicPath);
        $this->fileStorageIndex
            ->index($destPublicPath, false, $stream->getSize() ?? 0)
            ->flush();
    }

    /**
     * Get all files inside specified path.
     *
     * @param string $publicPath Public path to directory.
     *
     * @return FileListInterface
     */
    public function listFiles(string $publicPath = '/'): FileListInterface
    {
        if ($publicPath === '/') {
            $publicPath = null;
        }

        return $this->fileStorageIndex->createList($publicPath);
    }

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPublicPath, string $destPublicPath)
    {
        $this->fileStorage->move($srcPublicPath, $destPublicPath);
        $this->fileStorageIndex
            ->move($srcPublicPath, $destPublicPath)
            ->flush();
    }

    /**
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath)
    {
        $this->fileStorage->remove($publicPath);
        $this->fileStorageIndex
            ->remove($publicPath)
            ->flush();
    }

    /**
     * @param string $publicPath Public path to file.
     *
     * @return StreamInterface
     */
    public function read(string $publicPath): StreamInterface
    {
        return $this->fileStorage->read($publicPath);
    }
}
