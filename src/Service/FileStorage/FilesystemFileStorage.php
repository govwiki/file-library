<?php

namespace App\Service\FileStorage;

use App\Service\FileStorage\FileList\FileListInterface;
use App\Service\FileStorage\FileList\FilesystemFileList;
use Psr\Http\Message\StreamInterface;
use Slim\Http\Stream;

/**
 * Class FilesystemFileStorage
 *
 * @package App\Service\FileStorage
 */
class FilesystemFileStorage implements FileStorageInterface
{

    /**
     * @var string
     */
    private $root;

    /**
     * FilesystemFileStorage constructor.
     *
     * @param string $root Root of filesystem where documents is token place.
     */
    public function __construct(string $root)
    {
        $path = realpath($root);
        if (! is_string($path) || ! is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Path "%s" is not exists or not available for writing',
                $root
            ));
        }

        $this->root = rtrim($path, '/') . '/';
    }

    /**
     * @param StreamInterface $stream         Source file content as stream.
     * @param string          $destPublicPath Destination file path.
     *
     * @return void
     */
    public function store(StreamInterface $stream, string $destPublicPath)
    {
        $absDestPath = $this->root . ltrim($destPublicPath, '/');

        $this->creteDirectoriersTo($absDestPath);
        file_put_contents($absDestPath, $stream->getContents());
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
        return new FilesystemFileList($this->buildAbsPath($publicPath));
    }

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPublicPath, string $destPublicPath)
    {
        if ($srcPublicPath === $destPublicPath) {
            return;
        }

        $absSrcPath = $this->buildAbsPath($srcPublicPath);
        $absDestPath = $this->root . ltrim($destPublicPath, '/');

        $this->creteDirectoriersTo($absDestPath);

        //
        // Move file to specified directory.
        //
        if (! @rename($absSrcPath, $absDestPath)) {
            throw new FileStorageException(sprintf(
                'Can\'t store file "%s" to "%s"',
                $srcPublicPath,
                $destPublicPath
            ));
        }
    }

    /**
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath)
    {
        try {
            $absPath = $this->buildAbsPath($publicPath);
        } catch (FileStorageException $exception) {
            return;
        }

        if (\is_dir($absPath)) {
            $files = $this->listFiles($publicPath);

            /** @var \DirectoryIterator $file */
            foreach ($files as $file) {
                $this->remove($publicPath .'/'. $file->getFilename());
            }

            if (! @rmdir($absPath)) {
                throw new FileStorageException(sprintf(
                    'Can\'t remove directory "%s"',
                    $publicPath
                ));
            }
        } else {
            if (! @unlink($absPath)) {
                throw new FileStorageException(sprintf(
                    'Can\'t remove file "%s"',
                    $publicPath
                ));
            }
        }
    }

    /**
     * @param string $publicPath Public path to readed file.
     *
     * @return StreamInterface
     */
    public function read(string $publicPath): StreamInterface
    {
        $absPath = $this->buildAbsPath($publicPath);

        if (! is_file($this->root . $publicPath) || ! is_readable($absPath)) {
            throw new \LogicException(sprintf(
                'Can\'t read content of file "%s" \'cause it not ordinal file or not readable',
                $publicPath
            ));
        }

        $file = fopen($absPath, 'rb');
        if (! is_resource($file)) {
            throw new \RuntimeException(sprintf(
                'Can\'t read content of file "%s" \'cause it not ordinal file or not readable',
                $absPath
            ));
        }

        return new Stream($file);
    }

    /**
     * @param string $filePath A file path to which we should create directories.
     *
     * @return void
     */
    private function creteDirectoriersTo(string $filePath)
    {
        //
        // Create destination directory.
        //
        $filePathParts = explode('/', $filePath);
        unset($filePathParts[count($filePathParts) - 1]);
        $destDir = implode('/', $filePathParts);

        if (! is_dir($destDir)) {
            if (! @mkdir($destDir, 0777, true) || ! is_dir($destDir)) {
                throw new FileStorageException(sprintf(
                    'Can\'t create directories to "%s"',
                    $filePath
                ));
            }
        }
    }

    /**
     * @param string $publicPath A file public path.
     *
     * @return string
     */
    private function buildAbsPath(string $publicPath): string
    {
        $absPath = realpath($this->root . $publicPath);
        if (! is_string($absPath)) {
            throw new FileStorageException(sprintf(
                'Can\'t build absolute path for "%s"',
                $publicPath
            ));
        }

        if (strpos($absPath, rtrim($this->root, '/')) === false) {
            throw new FileStorageException(sprintf(
                'Invalid public path "%s"',
                $publicPath
            ));
        }

        return $absPath;
    }
}
