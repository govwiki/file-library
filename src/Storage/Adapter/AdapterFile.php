<?php

namespace App\Storage\Adapter;

/**
 * Class AdapterFile
 *
 * @package App\Storage\Adapter
 */
class AdapterFile
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var integer|null
     */
    private $size;

    /**
     * @var boolean
     */
    private $directory;

    /**
     * AdapterFile constructor.
     *
     * @param string       $path      Path to file.
     * @param boolean      $directory True if file is directory.
     * @param integer|null $size      Size of file.
     */
    private function __construct(string $path, bool $directory, int $size = null)
    {
        $this->path = $path;
        $this->directory = $directory;
        $this->size = $size;
    }

    /**
     * @param string  $path Path to file.
     * @param integer $size File size.
     *
     * @return AdapterFile
     */
    public static function createFile(string $path, int $size): AdapterFile
    {
        return new self($path, false, $size);
    }

    /**
     * @param string $path Path to directory.
     *
     * @return AdapterFile
     */
    public static function createDirectory(string $path): AdapterFile
    {
        return new self($path, true);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return integer|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return bool
     */
    public function isDirectory(): bool
    {
        return $this->directory;
    }
}
