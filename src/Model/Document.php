<?php

namespace App\Model;

use Assert\Assert;
use Assert\Assertion;

/**
 * Class Document
 *
 * @package App\Model
 */
class Document
{

    /**
     * @var integer|null
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $state;

    /**
     * @var integer
     */
    private $year;

    /**
     * @var string
     */
    private $path;

    /**
     * @var integer
     */
    private $fileSize;

    /**
     * @var \DateTime
     */
    private $uploadedAt;

    /**
     * @var User|null
     */
    private $uploadedBy;

    /**
     * Document constructor.
     *
     * @param string  $name       Full document name.
     * @param string  $state      For which state this document contains information.
     * @param integer $year       For which year this document contains information.
     * @param string  $path       Path do document file.
     * @param integer $fileSize   File size in bytes.
     * @param User    $uploadedBy Who upload this document.
     */
    public function __construct(
        string $name,
        string $state,
        int $year,
        string $path,
        int $fileSize,
        User $uploadedBy
    ) {
        $state = strtoupper($state);

        Assert::lazy()
            ->that($name, 'name')->notBlank()->maxLength(255)
            ->that($state, 'state')->notBlank()->length(2)
            ->that($year, 'year')->greaterThan(0)
            ->that($path, 'year')->notBlank()->maxLength(255)
            ->that($fileSize, 'fileSize')->greaterThan(0)
            ->tryAll();

        $this->name = $name;
        $this->state = $state;
        $this->year = $year;
        $this->path = $path;
        $this->fileSize = $fileSize;
        $this->uploadedAt = new \DateTime();
        $this->uploadedBy = $uploadedBy;
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
        Assert::that($name)->notBlank()->maxLength(255);
        $this->name = $name;

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
}
