<?php

namespace App\Service\Storage\Physical;

use App\Service\Storage\FileInterface;
use Assert\Assertion;

/**
 * Class File
 *
 * @package App\Service\\Storage\Physical
 */
class File implements FileInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var boolean
     */
    private $directory;

    /**
     * InternalFile constructor.
     *
     * @param string  $name      File name.
     * @param boolean $directory True if this file is directory.
     * @param integer $size      Size of file.
     */
    private function __construct(string $name, bool $directory, int $size = 0)
    {
        $this->name = $name;
        $this->size = $size;
        $this->directory = $directory;
    }

    /**
     * @param string  $name Document name.
     * @param integer $size Size of document.
     *
     * @return File
     */
    public static function createDocument(string $name, int $size): File
    {
        Assertion::notEmpty($name);
        Assertion::greaterOrEqualThan($size, 0);

        return new static($name, false, $size);
    }

    /**
     * @param string $name Directory name.
     *
     * @return File
     */
    public static function createDirectory(string $name): File
    {
        Assertion::notEmpty($name);

        return new static($name, true);
    }

    /**
     * Return file name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return file size in bytes.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return boolean
     */
    public function isDirectory(): bool
    {
        return $this->directory;
    }
}
