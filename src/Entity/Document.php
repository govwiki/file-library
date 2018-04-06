<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Document
 *
 * @ORM\Entity
 *
 * @package App\Entity
 */
class Document extends AbstractFile
{

    /**
     * Document file extension.
     *
     * @var string
     *
     * @ORM\Column
     */
    public $ext;

    /**
     * AbstractFile constructor.
     *
     * @param string    $name       A filename without extension.
     * @param string    $ext        A file extension.
     * @param string    $publicPath A public path to file.
     * @param string    $slug       A filename slug.
     * @param integer   $fileSize   A file size.
     * @param Directory $parent     A parent directory.
     */
    public function __construct(
        string $name,
        string $ext,
        string $publicPath,
        string $slug,
        int $fileSize,
        Directory $parent = null
    ) {
        parent::__construct($name, $publicPath, $slug, $fileSize, $parent);

        $this->ext = $ext;
    }

    /**
     * @return string
     */
    public function getExt(): string
    {
        return $this->ext;
    }

    /**
     * @param string $ext A file extension.
     *
     * @return $this
     */
    public function setExt(string $ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['type'] = 'document';
        $data['ext'] = $this->ext;

        return $data;
    }

    /**
     * @return boolean
     */
    public function isDirectory(): bool
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function isDocument(): bool
    {
        return true;
    }
}
