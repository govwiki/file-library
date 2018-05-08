<?php

namespace App\Storage\Index;

use App\Entity\AbstractFile;
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
     * @param string $path Path to directory.
     *
     * @return FileListBuilderInterface
     */
    public function createFileListBuilder(string $path): FileListBuilderInterface
    {
        return new ORMFileListBuilder($this->em, $path);
    }

    /**
     * @param string $srcPath  Path to moved file.
     * @param string $destPath Destination path.
     *
     * @return void
     */
    public function move(string $srcPath, string $destPath)
    {
        if ($srcPath === $destPath) {
            return;
        }

        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);
        $file = $repository->findByPublicPath($srcPath);

        if ($file !== null) {
            $this->em->remove($file);
            $this->index($file->getPublicPath(), $file->isDirectory(), $file->getFileSize());
        }
    }

    /**
     * @param string  $path        Path to indexed file.
     * @param boolean $isDirectory True if indexed file is directory.
     * @param integer $size        Size of indexed file, make sense only for documents.
     *
     * @return void
     */
    public function index(string $path, bool $isDirectory, int $size = 0)
    {
        $this->deferIndex($path, $isDirectory, $size);
        $this->flush();
    }

    /**
     * @param string  $path        Path to indexed file.
     * @param boolean $isDirectory True if indexed file is directory.
     * @param integer $size        Size of indexed file, make sense only for documents.
     *
     * @return void
     *
     * @see StorageIndexInterface::flush()
     */
    public function deferIndex(string $path, bool $isDirectory, int $size = 0)
    {
        $parts = explode('/', $path);
        array_shift($parts);

        if ($isDirectory) {
            $directory = $this->entityFactory->createDirectoryByPath($parts);
            $this->em->persist($directory);
        } else {
            $name = array_pop($parts);
            $directory = $this->entityFactory->createDirectoryByPath($parts);

            $this->em->persist($directory);
            $this->em->persist($this->entityFactory->createDocument(
                $name,
                $size,
                $directory
            ));
        }

        if (++$this->deferredBucketSize >= self::MAX_DEFERRED_BUCKET_SIZE) {
            $this->flush();
        }
    }

    /**
     * @param string $path A removed indexed file.
     *
     * @return void
     */
    public function remove(string $path)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $file = $repository->findByPublicPath($path);
        if ($file !== null) {
            $this->em->remove($file);
        }
    }

    /**
     * Clear whole index.
     *
     * @return void
     */
    public function clearIndex()
    {
        $connection = $this->em->getConnection();
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->em->getClassMetadata(AbstractFile::class);

        $connection->exec('SET FOREIGN_KEY_CHECKS = 0');
        $connection->exec(sprintf('DELETE FROM %s', $metadata->getTableName()));
        $connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Flush changes.
     *
     * @return void
     */
    public function flush()
    {
        $this->deferredBucketSize = 0;
        $this->em->flush();
    }
}
