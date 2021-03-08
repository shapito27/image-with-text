<?php

class ImageWithTextTest extends \PHPUnit\Framework\TestCase
{
    private $imageGenerator;

    public function setUp():void
    {
        $this->imageGenerator = new \Shapito27\ImageCreator\Services\ImageGenerator();
    }

    public function testExceptionOptionsNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->imageGenerator->generate();
    }

    public function testExceptionOnlySourceImageSet()
    {
        $this->expectException(RuntimeException::class);
        $this->imageGenerator->setSourceImagePath(__DIR__ . '/../example/images/source/sky.jpg')->generate();
    }

    public function testExceptionOnlySourceImageAndTextSet()
    {
        $this->expectException(RuntimeException::class);
        $this->imageGenerator
            ->setSourceImagePath(__DIR__ . '/../example/images/source/sky.jpg')
            ->setText('Test text')
            ->generate();
    }

    public function testResultFileCreated()
    {
        $this->imageGenerator
            ->setSourceImagePath(__DIR__ . '/../../example/images/source/sky.jpg')
            ->setText('Test text')
            ->generate();

        $this->assertFileExists($this->imageGenerator->getResultImagePath());
    }

    public function testBackupFileCreated()
    {
        $this->imageGenerator
            ->setSourceImagePath(__DIR__ . '/../../example/images/source/sky.jpg')
            ->setText('Test text')
            ->setSaveBackup(true)
            ->generate();

        $this->assertFileExists($this->imageGenerator->getBackupImagePath());
    }
}
