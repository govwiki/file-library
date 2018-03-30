<?php

namespace App\Service\FileStorage;

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

        $this->root = rtrim($path, '/');
    }

    /**
     * @param string $src  Source file path.
     * @param string $dest Destination file path.
     *
     * @return string Stored file unique key.
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

        $destPath = $this->root . '/' . ltrim($dest, '/');

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

        //
        // Prepare unique key - absolute path to stored file.
        //
        $destPath = realpath($destPath);
        if (! is_string($destPath)) {
            throw new FileStorageException(sprintf(
                'Can\'t store file "%s" to "%s"',
                $src,
                $dest
            ));
        }

        return $destPath;
    }

    /**
     * @param string $uniqueKey Removed file unique key.
     *
     * @return void
     */
    public function remove(string $uniqueKey)
    {
        if (! @unlink($uniqueKey)) {
            throw new FileStorageException(sprintf(
                'Can\'t remove file "%s"',
                $uniqueKey
            ));
        }
    }
}
