<?php

namespace App\Storage;

use App\Storage\Adapter\File\File as AdapterFile;
use App\Storage\Index\StorageIndexInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class File
 *
 * @package App\Storage
 */
class File extends AbstractFile
{

    /**
     * File constructor.
     *
     * @param AdapterFile           $file  Internal file from adapter.
     * @param StorageIndexInterface $index StorageIndexInterface instance.
     */
    public function __construct(
        AdapterFile $file,
        StorageIndexInterface $index
    ) {
        parent::__construct($file, $index);
    }

    /**
     * Get size of file in bytes.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->file->getSize();
    }

    /**
     * Get file content.
     *
     * @return StreamInterface
     */
    public function getContent(): StreamInterface
    {
        return $this->file->getContent();
    }
}
