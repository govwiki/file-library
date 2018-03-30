<?php

namespace App\Entity;

use Assert\Assert;
use Assert\Assertion;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Directory
 *
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 *
 * @package App\Entity
 */
class Directory implements \JsonSerializable
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
     * @var \DateTime
     */
    private $createdAt;

    /**
     * Document constructor.
     *
     * @param string $name Directory name.
     * @param string $slug Slug for document name.
     */
    public function __construct(
        string $name,
        string $slug
    ) {
        /** @psalm-suppress MixedMethodCall */
        Assert::lazy()
            ->that($name, 'name')->notBlank()->maxLength(255)
            ->that($slug, 'slug')->notBlank()->maxLength(255)
            ->tryAll();

        $this->name = $name;
        $this->slug = $slug;
        $this->createdAt = new \DateTime();
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
        ];
    }
}
