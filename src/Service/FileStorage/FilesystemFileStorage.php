<?php

namespace App\Service\FileStorage;

use App\Service\FileStorage\FileList\FileListInterface;
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
     * @param string $src  Source file path.
     * @param string $dest Destination file path.
     *
     * @return string Public path to file.
     */
    public function store(string $src, string $dest): string
    {
        $srcPath = realpath($src);
        if (! is_string($srcPath) || ! is_readable($srcPath)) {
            throw new FileStorageException(sprintf(
                'Source file "%s" is not exists or not readable',
                $src
            ));
        }

        $destPath = $this->root . ltrim($dest, '/');

        //
        // Create destination directory.
        //
        $destPathParts = explode('/', $destPath);
        unset($destPathParts[count($destPathParts) - 1]);
        $destDir = implode('/', $destPathParts);

        if (! is_dir($destDir)) {
            if (! @mkdir($destDir, 0777, true) || ! is_dir($destDir)) {
                throw new FileStorageException(sprintf(
                    'Can\'t store file "%s" to "%s"',
                    $src,
                    $dest
                ));
            }
        }

        //
        // Move file to specified directory.
        //
        if (! @rename($srcPath, $destPath)) {
            throw new FileStorageException(sprintf(
                'Can\'t store file "%s" to "%s"',
                $src,
                $dest
            ));
        }

        return $dest;
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
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath)
    {
        $absPath = $this->buildAbsPath($publicPath);
        if (! \file_exists($absPath)) {
            return;
        }

        if (! @unlink($absPath)) {
            throw new FileStorageException(sprintf(
                'Can\'t remove file "%s"',
                $publicPath
            ));
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

        if (strpos($absPath, $this->root) === false) {
            throw new FileStorageException(sprintf(
                'Invalid public path "%s"',
                $publicPath
            ));
        }

        return $absPath;
    }
}
