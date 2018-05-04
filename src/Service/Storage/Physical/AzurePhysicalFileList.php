<?php

namespace App\Service\Storage\Physical;

use MicrosoftAzure\Storage\File\FileRestProxy;

/**
 * Class AzurePhysicalFileList
 *
 * @package App\Service\Storage\Physical
 */
class AzurePhysicalFileList implements PhysicalFileListInterface
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
     * @var string
     */
    private $path;

    /**
     * AzureFileList constructor.
     *
     * @param FileRestProxy $client A FileRestProxy instance.
     * @param string        $share  Share name which is used for accessing files.
     * @param string        $path   Path to directory which we should list.
     */
    public function __construct(FileRestProxy $client, string $share, string $path)
    {
        $this->client = $client;
        $this->share = $share;
        $this->path = $path;
    }

    /**
     * Count elements of an object.
     *
     * @return integer
     */
    public function count()
    {
        return \count(iterator_to_array($this->getIterator()));
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     * @psalm-return \Traversable<int, \App\Service\Storage\FileInterface>
     */
    public function getIterator(): \Traversable
    {
        $result = $this->client->listDirectoriesAndFiles($this->share, $this->path);

        foreach ($result->getDirectories() as $directory) {
            yield File::createDirectory($directory->getName());
        }

        foreach ($result->getFiles() as $file) {
            yield File::createDocument($file->getName(), $file->getLength());
        }
    }
}
