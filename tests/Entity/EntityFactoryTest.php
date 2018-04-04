<?php

namespace App\Entity;

use App\Repository\DirectoryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\AppTestCase;

/**
 * Class EntityFactoryTest
 *
 * @package App\Entity
 */
class EntityFactoryTest extends AppTestCase
{

    /**
     * @var DirectoryRepositoryInterface|MockObject
     */
    private $directoryRepository;

    /**
     * @var EntityFactory
     */
    private $factory;

    /**
     * @return void
     */
    public function testCreateDocument()
    {
        $dir = $this->factory->createDirectory('some');
        $document = $this->factory->createDocument('doc.pdf', 1024, $dir);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('doc', $document->getName());
        $this->assertEquals('pdf', $document->getExt());
        $this->assertEquals(1024, $document->getFileSize());
        $this->assertEquals($dir, $document->getParent());
    }

    /**
     * @return void
     */
    public function testCreateDirectory()
    {
        $dir1 = $this->factory->createDirectory('some');

        $this->assertInstanceOf(Directory::class, $dir1);
        $this->assertEquals('some', $dir1->getName());
        $this->assertNull($dir1->getParent());

        $dir2 = $this->factory->createDirectory('some2', $dir1);

        $this->assertInstanceOf(Directory::class, $dir2);
        $this->assertEquals('some2', $dir2->getName());
        $this->assertEquals($dir1, $dir2->getParent());
    }

    /**
     * @return void
     */
    public function testCreateDirectoryByPath()
    {
        $path = [ 'some', 'dir' ];

        $this->directoryRepository
            ->expects($this->once())
            ->method('getByNameAndParent')
            ->with($this->equalTo('some'), $this->isNull())
            ->willReturn(null);

        $dir = $this->factory->createDirectoryByPath($path);

        $this->assertInstanceOf(Directory::class, $dir);
        $this->assertEquals('dir', $dir->getName());
        $this->assertEquals('/some/dir', $dir->getPublicPath());
        $this->assertNull($dir->getId());
        $this->assertNotNull($dir->getParent());

        $parent = $dir->getParent();

        $this->assertInstanceOf(Directory::class, $parent);
        $this->assertEquals('some', $parent->getName());
        $this->assertEquals('/some', $parent->getPublicPath());
        $this->assertNull($parent->getId());
        $this->assertNull($parent->getParent());
    }

    /**
     * @return void
     */
    public function testCreateDirectoryWithPartiallyExistsPath()
    {
        $someDir = new Directory('some', '/some', 'some');
        $deepDir = new Directory('deep', '/some/deep', 'deep', $someDir);

        $this->setProperty($someDir, 'id', 1);
        $this->setProperty($deepDir, 'id', 2);

        $path = [ 'some', 'deep', 'dir', 'dest' ];

        $this->directoryRepository
            ->expects($this->at(0))
            ->method('getByNameAndParent')
            ->with($this->equalTo('some'), $this->isNull())
            ->willReturn($someDir);

        $this->directoryRepository
            ->expects($this->at(1))
            ->method('getByNameAndParent')
            ->with($this->equalTo('deep'), $this->equalTo(1))
            ->willReturn($deepDir);

        $this->directoryRepository
            ->expects($this->at(2))
            ->method('getByNameAndParent')
            ->with($this->equalTo('dir'), $this->equalTo(2))
            ->willReturn(null);

        $dir = $this->factory->createDirectoryByPath($path);

        $this->assertInstanceOf(Directory::class, $dir);
        $this->assertEquals('dest', $dir->getName());
        $this->assertEquals('/some/deep/dir/dest', $dir->getPublicPath());
        $this->assertNull($dir->getId());
        $this->assertNotNull($dir->getParent());

        $parent = $dir->getParent();
        $this->assertInstanceOf(Directory::class, $parent);
        $this->assertEquals('dir', $parent->getName());
        $this->assertEquals('/some/deep/dir', $parent->getPublicPath());
        $this->assertNull($dir->getId());
        $this->assertNotNull($parent->getParent());

        $parent = $parent->getParent();
        $this->assertEquals($deepDir, $parent);
        $this->assertEquals('/some/deep', $parent->getPublicPath());
        $this->assertNotNull($parent->getParent());

        $parent = $parent->getParent();
        $this->assertEquals($someDir, $parent);
        $this->assertEquals('/some', $parent->getPublicPath());
        $this->assertNull($parent->getParent());
    }


    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->directoryRepository = $this->createMockForInterface(DirectoryRepositoryInterface::class);
        $this->factory = new EntityFactory($this->directoryRepository);
    }
}
