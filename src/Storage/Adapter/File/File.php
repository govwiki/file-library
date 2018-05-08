<?php

namespace App\Storage\Adapter\File;

use App\Storage\Adapter\StorageAdapterInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class File
 *
 * @package App\Storage\Adapter\File
 */
class File extends AbstractFile
{

    /**
     * @var integer|null
     */
    private $size;

    /**
     * @var StreamInterface|null
     */
    private $content;

    /**
     * File constructor.
     *
     *
     * @param StorageAdapterInterface $adapter A StorageAdapterInterface instance.
     * @param string                  $path    Path to file.
     * @param int|null                $size    Size of file.
     * @param StreamInterface|null    $content File content.
     */
    public function __construct(
        StorageAdapterInterface $adapter,
        string $path,
        int $size = null,
        StreamInterface $content = null
    ) {
        parent::__construct($adapter, $path);

        $this->size = $size;
        $this->content = $content;
    }

    /**
     * Get size of file in bytes.
     *
     * @return integer
     */
    public function getSize(): int
    {
        if ($this->size === null) {
            $this->size = $this->getContent()->getSize();
        }

        return $this->size;
    }

    /**
     * Get file content.
     *
     * @return StreamInterface
     */
    public function getContent(): StreamInterface
    {
        if ($this->content === null) {
            $this->content = $this->adapter->read($this->path);
        }

        return $this->content;
    }
}
