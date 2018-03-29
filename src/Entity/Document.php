<?php

namespace App\Entity;

use Assert\Assert;
use Assert\Assertion;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Document
 *
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 *
 * @package App\Entity
 */
class Document implements \JsonSerializable
{

    /**
     * @var integer|null
     *
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $typeSlug;

    /**
     * @var string
     *
     * @ORM\Column(length=2)
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $fileSize;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $uploadedAt;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="username")
     */
    private $uploadedBy;

    /**
     * Document constructor.
     *
     * @param string    $name       Full document name.
     * @param string    $slug       Slug for document path.
     * @param string    $type       Document type.
     * @param string    $typeSlug   Slug for document type.
     * @param string    $state      For which state this document contains
     *                              information.
     * @param integer   $year       For which year this document contains
     *                              information.
     * @param string    $path       Path do document file.
     * @param integer   $fileSize   File size in bytes.
     * @param User|null $uploadedBy Who upload this document.
     */
    public function __construct(
        string $name,
        string $slug,
        string $type,
        string $typeSlug,
        string $state,
        int $year,
        string $path,
        int $fileSize,
        User $uploadedBy = null
    ) {
        $state = strtoupper($state);

        /** @psalm-suppress MixedMethodCall */
        Assert::lazy()
            ->that($name, 'name')->notBlank()->maxLength(255)
            ->that($slug, 'slug')->notBlank()->maxLength(255)
            ->that($type, 'type')->notBlank()->maxLength(255)
            ->that($typeSlug, 'typeSlug')->notBlank()->maxLength(255)
            ->that($state, 'state')->notBlank()->length(2)
            ->that($year, 'year')->greaterThan(0)
            ->that($path, 'path')->notBlank()
            ->that($fileSize, 'fileSize')->greaterThan(0)
            ->tryAll();

        $this->name = $name;
        $this->slug = $slug;
        $this->type = $type;
        $this->typeSlug = $typeSlug;
        $this->state = $state;
        $this->year = $year;
        $this->path = $path;
        $this->fileSize = $fileSize;
        $this->uploadedBy = $uploadedBy;
        $this->uploadedAt = new \DateTime();
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type Document type.
     *
     * @return $this
     */
    public function setType(string $type)
    {
        /** @psalm-suppress MixedMethodCall */
        Assert::that($type)->notBlank()->maxLength(255);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getTypeSlug(): string
    {
        return $this->typeSlug;
    }

    /**
     * @param string $typeSlug A new type slug.
     *
     * @return $this
     */
    public function setTypeSlug(string $typeSlug)
    {
        $this->typeSlug = $typeSlug;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state New document state. Should be valid two letters ANSI
     *                      state code.
     *
     * @return $this
     *
     * @link https://en.wikipedia.org/wiki/List_of_U.S._state_abbreviations
     */
    public function setState(string $state)
    {
        /** @psalm-suppress MixedMethodCall */
        Assert::that($state)->notBlank()->maxLength(2);
        $this->state = strtoupper($state);

        return $this;
    }

    /**
     * @return integer
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param integer $year New document year.
     *
     * @return $this
     */
    public function setYear(int $year)
    {
        Assertion::greaterThan($year, 0);
        $this->year = $year;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path New file path to document.
     *
     * @return $this
     */
    public function setPath(string $path)
    {
        /** @psalm-suppress MixedMethodCall */
        Assert::that($path)->notBlank()->maxLength(255);
        $this->path = $path;

        return $this;
    }

    /**
     * @return integer
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * @param integer $fileSize Document file size in bytes.
     *
     * @return $this
     */
    public function setFileSize(int $fileSize)
    {
        Assertion::greaterThan($fileSize, 0);
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUploadedAt(): \DateTime
    {
        return $this->uploadedAt;
    }

    /**
     * @param \DateTime $uploadedAt When this document is uploaded.
     *
     * @return $this
     */
    public function setUploadedAt(\DateTime $uploadedAt)
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUploadedBy()
    {
        return $this->uploadedBy;
    }

    /**
     * @param User $uploadedBy Who is upload this document.
     *
     * @return $this
     */
    public function setUploadedBy(User $uploadedBy = null)
    {
        $this->uploadedBy = $uploadedBy;

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
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'state' => $this->state,
            'year' => $this->year,
            'fileSize' => $this->fileSize,
        ];
    }
}
