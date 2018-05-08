<?php

namespace App\Storage;

use App\Storage\Adapter\File\Directory as AdapterDirectory;
use App\Storage\Index\StorageIndexInterface;

/**
 * Class Directory
 *
 * @package App\Storage
 */
class Directory extends AbstractFile
{

    /**
     * Directory constructor.
     *
     * @param AdapterDirectory      $directory Internal directory from adapter.
     * @param StorageIndexInterface $index     A StorageIndexInterface instance.
     */
    public function __construct(
        AdapterDirectory $directory,
        StorageIndexInterface $index
    ) {
        parent::__construct($directory, $index);
    }

    /**
     * @return FileListBuilderInterface
     */
    public function getListBuilder(): FileListBuilderInterface
    {
        return $this->index->createFileListBuilder($this->file->getPath());
    }
}
