<?php

namespace App\Service\DocumentMover;

use App\Entity\Directory;
use App\Entity\Document;
use App\Storage\Storage;

/**
 * Class DocumentMoverService
 *
 * @package App\Service\DocumentMover
 */
class DocumentMoverService
{

    const FILENAME_PATTERN = '/(?P<year>\d{4})\.\w+$/';

    /**
     * @var Storage
     */
    private $storage;

    /**
     * DocumentMoverService constructor.
     *
     * @param Storage $storage A Storage instance.
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
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
        $file = $this->storage->getFile($document->getPublicPath());

        if ($file === null) {
            return;
        }

        //
        // Build path to top level directory.
        //
        $path = '/';
        $newTopLevelDirectory = $topLevelDirectory ?? $document->getTopLevelDir();
        if ($newTopLevelDirectory !== null) {
            $path = $newTopLevelDirectory->getPublicPath();
        }

        if ($name === null) {
            $name = $document->getName() .'.'. $document->getExt();
        }

        $year = $this->getYearFromDocumentName($name);

        $filePath = $path .'/'. $year .'/'. $name;
        if ($this->storage->isFileExists($filePath)) {
            throw new DocumentMoverException(\sprintf('File "%s" is already exists', $filePath));
        }

        $file->move($filePath);
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
