<?php

namespace App\Service\FileStorage\Index;

use App\Entity\AbstractFile;
use App\Entity\EntityFactory;
use App\Repository\FileRepositoryInterface;
use App\Service\FileStorage\FileList\FileListInterface;
use App\Service\FileStorage\FileList\ORMIndexFileList;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class ORMFileStorageIndex
 *
 * @package App\Service\FileStorage\Index
 */
class ORMFileStorageIndex implements FileStorageIndexInterface
{

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ORMFileStorageIndex constructor.
     *
     * @param EntityFactory          $entityFactory A EntityFactory instance.
     * @param EntityManagerInterface $em            A EntityManagerInterface instance.
     */
    public function __construct(EntityFactory $entityFactory, EntityManagerInterface $em)
    {
        $this->entityFactory = $entityFactory;
        $this->em = $em;
    }

    /**
     * Get all files inside specified path.
     *
     * @param string|null $publicPath Public path to directory.
     *
     * @return FileListInterface
     */
    public function createList(string $publicPath = null): FileListInterface
    {
        return new ORMIndexFileList($this->em, $publicPath);
    }

    /**
     * Add specified file to index.
     *
     * @param string  $publicPath  Public path to indexed file.
     * @param boolean $isDirectory Index as directory if set.
     * @param integer $fileSize    Indexed file size. Ignored if $isDirectory is
     *                             true.
     *
     * @return $this
     */
    public function index(string $publicPath, bool $isDirectory = true, int $fileSize = 0)
    {
        $dirs = explode('/', $publicPath);
        array_shift($dirs);

        if ($isDirectory) {
            $directory = $this->entityFactory->createDirectoryByPath($dirs);
            $this->em->persist($directory);
        } else {
            $name = array_pop($dirs);
            $directory = $this->entityFactory->createDirectoryByPath($dirs);

            $this->em->persist($directory);
            $this->em->persist($this->entityFactory->createDocument(
                $name,
                $fileSize,
                $directory
            ));
        }

        return $this;
    }

    /**
     * @param string $srcPublicPath  Path to moved file.
     * @param string $destPublicPath Destination path.
     *
     * @return $this
     */
    public function move(string $srcPublicPath, string $destPublicPath)
    {
        if ($srcPublicPath === $destPublicPath) {
            return $this;
        }

        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $file = $repository->findByPublicPath($srcPublicPath);
        if ($file !== null) {
            $this->em->remove($file);
            $this->index($destPublicPath, false, $file->getFileSize());
        }

        return $this;
    }

    /**
     * Remove specified file from index.
     *
     * @param string $publicPath Public path to removed file.
     *
     * @return $this
     */
    public function remove(string $publicPath)
    {
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $file = $repository->findByPublicPath($publicPath);
        if ($file !== null) {
            $this->em->remove($file);
        }

        return $this;
    }

    /**
     * Clear index.
     *
     * @return $this
     */
    public function clear()
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
     * Apply index changes.
     *
     * @return $this
     */
    public function flush()
    {
        $this->em->flush();

        return $this;
    }
}
