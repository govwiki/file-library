<?php

namespace App\Service\FileStorage;

use App\Entity\AbstractFile;
use App\Entity\EntityFactory;
use App\Repository\FileRepositoryInterface;
use App\Service\FileStorage\FileList\FileListInterface;
use App\Service\FileStorage\FileList\ORMIndexFileList;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class ORMIndexFileStorage
 *
 * @package App\Service\FileStorage
 *
 * todo, refactoring. Move indexing code from this file storage into specified class.
 */
class ORMIndexFileStorage implements FileStorageInterface
{

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * ORMIndexFileStorage constructor.
     *
     * @param FileStorageInterface   $fileStorage   A FileStorageInterface
     *                                              instance.
     * @param EntityManagerInterface $em            A EntityManagerInterface
     *                                              instance.
     * @param EntityFactory          $entityFactory A EntityFactory instance.
     */
    public function __construct(
        FileStorageInterface $fileStorage,
        EntityManagerInterface $em,
        EntityFactory $entityFactory
    ) {
        $this->fileStorage = $fileStorage;
        $this->em = $em;
        $this->entityFactory = $entityFactory;
    }

    /**
     * @param string $src  Source file path.
     * @param string $dest Destination file path.
     *
     * @return string Public path to file.
     */
    public function store(string $src, string $dest): string
    {
        /** @var integer|boolean $fileSize */
        $fileSize = @filesize($src);
        if (! is_int($fileSize)) {
            throw new \RuntimeException(sprintf(
                'Can\'t get file size for "%s"',
                $src
            ));
        }

        $result = $this->fileStorage->store($src, $dest);

        $dirs = explode('/', $dest);
        $documentName = array_pop($dirs);
        $directory = $this->entityFactory->createDirectoryByPath($dirs);

        $this->em->persist($directory);
        $this->em->persist($this->entityFactory->createDocument(
            $documentName,
            $fileSize,
            $directory
        ));

        $this->em->flush();

        return $result;
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
        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        if ($publicPath === '/') {
            $publicPath = null;
        }

        return new ORMIndexFileList($repository->listFilesIn($publicPath));
    }

    /**
     * @param string $publicPath Public path to removed file.
     *
     * @return void
     */
    public function remove(string $publicPath)
    {
        $this->fileStorage->remove($publicPath);

        /** @var FileRepositoryInterface $repository */
        $repository = $this->em->getRepository(AbstractFile::class);

        $file = $repository->findByPublicPath($publicPath);
        if ($file !== null) {
            $this->em->remove($file);
            $this->em->flush($file);
        }
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
