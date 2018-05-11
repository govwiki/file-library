<?php

namespace App\Entity;

use Assert\Assert;
use Assert\Assertion;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AbstractFile
 *
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "directory" = "Directory",
 *     "document" = "Document"
 * })
 *
 * @package App\Entity
 */
abstract class AbstractFile implements \JsonSerializable
{

    /**
     * @var integer|null
     *
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column
     */
    protected $publicPath;

    /**
     * @var string
     *
     * @ORM\Column
     */
    protected $slug;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fileSize;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var Directory|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Directory", inversedBy="childes")
     */
    protected $parent;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $hidden = false;

    /**
     * AbstractFile constructor.
     *
     * @param string         $name       A filename.
     * @param string         $publicPath A public path to file.
     * @param string         $slug       A filename slug.
     * @param integer|null   $fileSize   A file size.
     * @param Directory|null $parent     A parent directory.
     */
    public function __construct(
        string $name,
        string $publicPath,
        string $slug,
        int $fileSize = null,
        Directory $parent = null
    ) {
        /** @psalm-suppress MixedMethodCall */
        Assert::lazy()
            ->that($name, 'name')->notBlank()->maxLength(255)
            ->that($slug, 'slug')->notBlank()->maxLength(255)
            ->tryAll();

        $this->publicPath = $publicPath;
        $this->fileSize = $fileSize;
        $this->name = $name;
        $this->slug = $slug;
        $this->parent = $parent;

        $this->createdAt = new \DateTime();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->publicPath;
    }

    /**
     * @return integer|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name New document name.
     *
     * @return $this
     */
    public function setName(string $name)
    {
        /** @psalm-suppress MixedMethodCall */
        Assert::that($name)->notBlank()->maxLength(255);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    /**
     * @param string $publicPath A public path to this file.
     *
     * @return $this
     */
    public function setPublicPath(string $publicPath)
    {
        $this->publicPath = $publicPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug A new name slug.
     *
     * @return $this
     */
    public function setSlug(string $slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return integer
     */
    public function getFileSize(): int
    {
        return $this->fileSize ?? 0;
    }

    /**
     * @param integer $fileSize Document file size in bytes.
     *
     * @return $this
     */
    public function setFileSize(int $fileSize)
    {
        Assertion::greaterOrEqualThan($fileSize, 0);
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt When this directory is created.
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Directory|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Directory|null $parent A parent directory.
     *
     * @return $this
     */
    public function setParent(Directory $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden Is file hidden or not.
     *
     * @return $this
     */
    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'createdAt' => $this->createdAt->format('c'),
            'parent' => $this->parent ? $this->parent->getId() : null,
            'publicPath' => $this->publicPath,
            'fileSize' => $this->fileSize,
        ];
    }

    /**
     * @return Directory|null Return null if try to get top level dir on one of
     *                        top level directory.
     */
    public function getTopLevelDir()
    {
        if ($this->parent === null) {
            return null;
        }

        return $this->doGetTopLevelDir($this->parent);
    }

    /**
     * @return boolean
     */
    abstract public function isDirectory(): bool;

    /**
     * @return boolean
     */
    abstract public function isDocument(): bool;

    /**
     * @param Directory $curr Currently processed directory.
     *
     * @return Directory
     */
    private function doGetTopLevelDir(Directory $curr): Directory
    {
        $parent = $curr->getParent();
        if ($parent === null) {
            return $curr;
        }

        return $this->doGetTopLevelDir($parent);
    }
}
