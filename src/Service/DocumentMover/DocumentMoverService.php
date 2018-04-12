<?php

namespace App\Service\DocumentMover;

use App\Entity\Directory;
use App\Entity\Document;
use App\Service\FileStorage\FileStorageInterface;

/**
 * Class DocumentMoverService
 *
 * @package App\Service\DocumentMover
 */
class DocumentMoverService
{

    const FILENAME_PATTERN = '/(?P<year>\d{4})$/';

    /**
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * DocumentMoverService constructor.
     *
     * @param FileStorageInterface $fileStorage A FileStorageInterface instance.
     */
    public function __construct(FileStorageInterface $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * @param Document    $document          Moved document instance.
     * @param Directory   $topLevelDirectory Top level directory where document
     *                                       should be.
     * @param string|null $name              New document name if it needed.
     *
     * @return void
     */
    public function move(Document $document, Directory $topLevelDirectory = null, string $name = null)
    {
        //
        // Build path to top level directory.
        //
        $path = '/';
        $newTopLevelDirectory = $topLevelDirectory ?? $document->getTopLevelDir();
        if ($newTopLevelDirectory !== null) {
            $path = $newTopLevelDirectory->getPublicPath();
        }

        if ($name === null) {
            $name = $document->getName();
        }

        $year = $this->getYearFromDocumentName($name);

        $this->fileStorage->move(
            $document->getPublicPath(),
            $path .'/'. $year .'/'. $name
        );
    }

    /**
     * @param string $name A new document name.
     *
     * @return string
     *
     * @psalm-suppress MixedInferredReturnType
     */
    private function getYearFromDocumentName(string $name): string
    {
        $matches = [];
        if ((preg_match(self::FILENAME_PATTERN, $name, $matches) !== 1) || ! isset($matches['year'])) {
            throw new DocumentMoverException(sprintf(
                'Can\'t determine year from document "%s"',
                $name
            ));
        }

        /** @psalm-suppress MixedReturnStatement */
        return $matches['year'];
    }
}
