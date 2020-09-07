<?php

namespace App\Storage\Index;

use App\Entity\AbstractFile;
use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\EntityFactory;
use App\Repository\FileRepositoryInterface;
use App\Storage\FileListBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Interface StorageIndexInterface
 *
 * @package App\Storage\Index
 */
class ORMStorageIndex implements StorageIndexInterface
{

    const MAX_DEFERRED_BUCKET_SIZE = 200;

    /**
     * @var integer
     */
    private $deferredBucketSize = 0;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Directory[]
     * @psalm-var Array<string, Directory>
     */
    private $createdDirectories = [];

    /**
     * ORMStorageIndex constructor.
     *
     * @param EntityFactory          $entityFactory
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityFactory $entityFactory,
        EntityManagerInterface $em
    ) {
        $this->entityFactory = $entityFactory;
        $this->em = $em;
    }

    /**
     * @param string $path Path to created directory.
     *
     * @return $this
     *
     * @api
     */
    public function createDirectory(string $path)
    {
        $this->createDirectoryByPath($path);

        return $this;
    }

    /**
     * @param string $path Path to required directory.
     *
     * @return Directory|null
     */
    public function getDirectory(string $path)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $directory = $repository->findByPublicPath($path);

        if (($directory === null) || (! $directory instanceof Directory)) {
            return null;
        }

        return $directory;
    }

    /**
     * @param string $slug Slug to required directory.
     *
     * @return Directory|null
     */
    public function getDirectoryBySlug(string $slug)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $directory = $repository->findBySlug($slug);

        if (($directory === null) || (! $directory instanceof Directory)) {
            return null;
        }

        return $directory;
    }

    /**
     * @param string  $path Path where file should be created.
     * @param integer $size Stored file size.
     *
     * @return $this
     *
     * @api
     */
    public function createFile(string $path, int $size)
    {
        $directoryPath = \dirname($path);

        $directory = $this->getDirectory($directoryPath);
        if ($directory === null) {
            $directory = $this->createDirectoryByPath($directoryPath);
        }

        $this->em->persist($this->entityFactory->createDocument(
            \basename($path),
            $size,
            $directory
        ));

        if (++$this->deferredBucketSize >= self::MAX_DEFERRED_BUCKET_SIZE) {
            $this->flush();
        }

        return $this;
    }

    /**
     * @param string $path Path to required file.
     *
     * @return Document|null
     */
    public function getFile(string $path)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $document = $repository->findByPublicPath($path);

        if (($document === null) || (! $document instanceof Document)) {
            return null;
        }

        return $document;
    }

    /**
     * @param string|null $path Path to directory.
     *
     * @return FileListBuilderInterface
     */
    public function createFileListBuilder(string $path = null): FileListBuilderInterface
    {
        return new ORMFileListBuilder($this->em, $path);
    }

    /**
     * @param string $srcPath  Path to moved file.
     * @param string $destPath Destination path.
     *
     * @return $this
     */
    public function move(string $srcPath, string $destPath)
    {
        if ($srcPath === $destPath) {
            return $this;
        }

        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);
        $file = $repository->findByPublicPath($srcPath);

        if ($file !== null) {
            $this->em->remove($file);
            $this->createFile($destPath, $file->getFileSize());
        }

        return $this;
    }

    /**
     * @param string $path A removed indexed file.
     *
     * @return $this
     */
    public function remove(string $path)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $file = $repository->findByPublicPath($path);
        if ($file !== null) {
            $this->em->remove($file);
        }

        if (++$this->deferredBucketSize >= self::MAX_DEFERRED_BUCKET_SIZE) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Clear whole index.
     *
     * @return $this
     */
    public function clearIndex()
    {
        $connection = $this->em->getConnection();
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->em->getClassMetadata(AbstractFile::class);

        $connection->exec('SET FOREIGN_KEY_CHECKS = 0');
        $connection->exec(sprintf('DELETE FROM %s', $metadata->getTableName()));
        $connection->exec('SET FOREIGN_KEY_CHECKS = 1');

        return $this;
    }

    /**
     * Flush changes.
     *
     * @return $this
     */
    public function flush()
    {
        $this->deferredBucketSize = 0;
        $this->createdDirectories = [];
        $this->em->flush();

        return $this;
    }

    /**
     * @param string $path Path to created directory.
     *
     * @return Directory
     */
    private function createDirectoryByPath(string $path): Directory
    {
        $parts = \explode('/', $path);
        \array_shift($parts);

        $tmp = $directory = $this->entityFactory->createDirectoryByPath($parts);
        $dirs = [];

        do {
            $this->em->persist($tmp);
            $dirs[] = $tmp;

            $tmp = $tmp->getParent();
        } while ($tmp !== null);

        //
        // All directories should be flushed immediately.
        //
        $this->em->flush($dirs);

        return $directory;
    }
}
