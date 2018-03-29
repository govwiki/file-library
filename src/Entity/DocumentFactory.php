<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;

/**
 * Class DocumentFactory
 *
 * @package App\Entity
 */
class DocumentFactory
{

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * DocumentFactory constructor.
     */
    public function __construct()
    {
        $this->slugify = new Slugify();
    }

    /**
     * @param string  $name     Full document name.
     * @param string  $type     Document type.
     * @param string  $state    For which state this document contains
     *                          information.
     * @param integer $year     For which year this document contains
     *                          information.
     * @param string  $path     Path do document file.
     * @param integer $fileSize File size in bytes.
     *
     * @return Document
     */
    public function createDocument(
        string $name,
        string $type,
        string $state,
        int $year,
        string $path,
        int $fileSize
    ) {
        return new Document(
            $name,
            $this->slugify->slugify($type .'/'. $state .'/'. $year .'/'. $name),
            $type,
            $this->slugify->slugify($type),
            $state,
            $year,
            $path,
            $fileSize
        );
    }
}
