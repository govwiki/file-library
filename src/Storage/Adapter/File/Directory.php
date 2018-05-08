<?php

namespace App\Storage\Adapter\File;

/**
 * Class Directory
 *
 * @package App\Storage\Adapter\File
 */
class Directory extends AbstractFile
{

    /**
     * @return \Traversable
     * @psalm-return \Traversable<AbstractFile>
     */
    public function listFiles(): \Traversable
    {
        return $this->adapter->listFiles($this->path);
    }
}
