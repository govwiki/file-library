<?php

namespace App\Storage;

/**
 * Class Directory
 *
 * @package App\Storage
 */
class Directory extends AbstractFile
{

    /**
     * @return FileListBuilderInterface
     */
    public function getListBuilder(): FileListBuilderInterface
    {
        $path = $this->path;
        if ($path === '/') {
            //
            // Because we don't index root directory.
            //
            $path = null;
        }
        return $this->index->createFileListBuilder($path);
    }
}
