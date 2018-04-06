<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Directory
 *
 * @ORM\Entity(repositoryClass="App\Repository\DirectoryRepository")
 *
 * @package App\Entity
 */
class Directory extends AbstractFile
{

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\AbstractFile", mappedBy="parent", cascade={ "ALL" })
     */
    private $childes;

    /**
     * AbstractFile constructor.
     *
     * @param string    $name       A filename.
     * @param string    $publicPath A public path to file.
     * @param string    $slug       A filename slug.
     * @param Directory $parent     A parent directory.
     */
    public function __construct(
        string $name,
        string $publicPath,
        string $slug,
        Directory $parent = null
    ) {
        parent::__construct($name, $publicPath, $slug, null, $parent);
        $this->childes = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getChildes(): Collection
    {
        return $this->childes;
    }

    /**
     * @param AbstractFile $file A added file.
     *
     * @return $this
     */
    public function addChild(AbstractFile $file)
    {
        $this->childes[] = $file->setParent($this);

        return $this;
    }

    /**
     * @param AbstractFile $file A removed file.
     *
     * @return $this
     */
    public function removeChild(AbstractFile $file)
    {
        $this->childes->removeElement($file->setParent(null));

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
        $data['type'] = 'directory';

        return $data;
    }

    /**
     * @return boolean
     */
    public function isDirectory(): bool
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function isDocument(): bool
    {
        return false;
    }
}
