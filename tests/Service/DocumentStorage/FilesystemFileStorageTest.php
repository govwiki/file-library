<?php

namespace App\Service\FileStorage;

use Tests\AppTestCase;

/**
 * Class FilesystemFileStorageTest
 *
 * @package App\Service\FileStorage
 */
class FilesystemFileStorageTest extends AppTestCase
{

    const FIXTURE_ROOT = __DIR__ .'/../../Fixtures/FileStorage/root';

    const FIXTURES = [
        'dir1' => [
            'dir11' => [
                'dir111' => [
                    'file1111' => null,
                    'file1112' => null,
                    'file1113' => null,
                ],
                'dir112' => [
                    'file1121' => null,
                    'file1122' => null,
                    'file1123' => null,
                ],
            ],
            'dir12' => [
                'dir121' => [
                    'file1211' => null,
                    'file1212' => null,
                    'file1213' => null,
                ],
                'dir122' => [
                    'file1221' => null,
                    'file1222' => null,
                    'file1223' => null,
                ],
            ],
        ],
        'dir2' => [
            'dir21' => [
                'dir211' => [
                    'file1211' => null,
                    'file1212' => null,
                    'file1213' => null,
                ],
            ],
        ],
    ];

    /**
     * @var FilesystemFileStorage
     */
    private $storage;

    /**
     * @return void
     */
    public function testStore()
    {
        $src = self::FIXTURE_ROOT.'/test';
        $dest = '/dir2/dir22/dir221/file2211';
        touch($src);

        $this->storage->store($src, $dest);
        $this->assertFileExists(self::FIXTURE_ROOT . $dest);
        $this->assertFileNotExists($src);
    }

    /**
     * @return void
     */
    public function testStoreAlreadyExistsFile()
    {
        $src = self::FIXTURE_ROOT.'/test';
        $dest = '/dir1/dir11/dir111/file1111';
        touch($src);

        $this->storage->store($src, $dest);
        $this->assertFileExists(self::FIXTURE_ROOT.$dest);
        $this->assertFileNotExists($src);
    }

    /**
     * @return void
     */
    public function testRemove()
    {
        $path = '/dir1/dir11/dir111/file1111';
        $this->storage->remove($path);
        $this->assertFileNotExists($path);
    }

    /**
     * @expectedException \App\Service\FileStorage\FileStorageException
     * @expectedExceptionMessage Can't build absolute path for "/dir1/dir11/dir111/file1155"
     *
     * @return void
     */
    public function testRemoveNotExistsFile()
    {
        $path = '/dir1/dir11/dir111/file1155';
        $this->storage->remove($path);
        $this->assertFileNotExists($path);
    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        @mkdir(self::FIXTURE_ROOT, 0777, true);
        self::createFixtures(self::FIXTURE_ROOT, self::FIXTURES);
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @return void
     */
    public static function tearDownAfterClass()
    {
        $iterator = new \RecursiveDirectoryIterator(self::FIXTURE_ROOT, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->storage = new FilesystemFileStorage(self::FIXTURE_ROOT);
    }

    /**
     * @param string $path  Path to fixture directory.
     * @param array  $files Fixtures.
     *
     * @return void
     */
    private static function createFixtures(string $path, array $files)
    {
        foreach ($files as $name => $subFiles) {
            if (is_array($subFiles)) {
                mkdir($path .'/'. $name);
                self::createFixtures($path .'/'. $name, $subFiles);
            } else {
                touch($path .'/'. $name);
            }
        }
    }
}
