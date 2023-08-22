<?php

namespace Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shapito27\ImageCreator\Services\ImageGenerator;

class ImageWithTextTest extends TestCase
{
    /**
     * @var ImageGenerator
     */
    private $imageGenerator;

    /**
     * @var array created files within test. Need for deleting files at the end of the test.
     */
    private $createdFiles = [];

    public function setUp(): void
    {
        $this->imageGenerator = new ImageGenerator();
    }

    protected function tearDown(): void
    {
        if (empty($this->createdFiles)) {
            return;
        }

        foreach ($this->createdFiles as $createdFile) {
            if (is_file($createdFile)) {
                unlink($createdFile);
            }
        }
    }

    public function testExceptionOptionsNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->imageGenerator->generate();
    }

    public function testExceptionOnlySourceImageSet()
    {
        $this->expectException(RuntimeException::class);
        $this->imageGenerator->setSourceImagePath(__DIR__ . '/resources/sky.jpg')->generate();
    }

//    public function testExceptionOnlySourceImageAndTextSet()
//    {
//        $this->expectException(RuntimeException::class);
//        $this->imageGenerator
//            ->setSourceImagePath(__DIR__ . '/resources/sky.jpg')
//            ->setText('Test text')
//            ->generate();
//    }

    public function testResultFileCreated()
    {
        $this->imageGenerator
            ->setSourceImagePath(__DIR__ . '/resources/sky.jpg')
            ->setText('Test text')
            ->generate();
        $this->createdFiles[] = $this->imageGenerator->getResultImagePath();
        $this->assertFileExists($this->imageGenerator->getResultImagePath());
    }

    public function testBackupFileCreated()
    {
        $this->imageGenerator
            ->setSourceImagePath(__DIR__ . '/resources/sky.jpg')
            ->setText('Test text')
            ->setSaveBackup(true)
            ->generate();
        $this->createdFiles[] = $this->imageGenerator->getResultImagePath();
        $this->createdFiles[] = $this->imageGenerator->getBackupImagePath();
        $this->assertFileExists($this->imageGenerator->getBackupImagePath());
    }
}
