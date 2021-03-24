<?php

namespace Shapito27\ImageCreator\Services;

use Shapito27\ImageCreator\Models\Color;
use RuntimeException;

/**
 * Class ImageGenerator
 *
 * @package Shapito27\ImageCreator\Services
 */
class ImageGenerator
{
    /** @var string */
    private $sourceImagePath;
    /** @var string */
    private $resultImagePath;
    /** @var string */
    private $text;
    /** @var int */
    private $textFontSize;
    /** @var string */
    private $fontPath;
    /** @var Color */
    private $textColor;
    /** @var int */
    private $coefficientLeftRightTextPadding;
    /** @var int */
    private $textLinesTopBottomPadding;// @todo make percent of font by default. But add ability user to change it in the future
    /** @var int */
    private $imageQuality;

    /** @var bool */
    private $saveBackup;
    /** @var string */
    private $backupImagePath;

    /** @var float 1 punct of font size */
    public const ONE_PUNCT_IN_PIXELS = 1.338;
    /** @var string  */
    public const DEFAULT_FONT_PATH = __DIR__ . '/../../example/font/merriweatherregular.ttf';

    public function __construct()
    {
        $this->setTextColor(new Color(Color::BLACK_COLOR));
        if(!file_exists(self::DEFAULT_FONT_PATH)) {
            throw new RuntimeException('Font file is not exist');
        }
        $this->setFontPath(self::DEFAULT_FONT_PATH);
        $this->setCoefficientLeftRightTextPadding(20);
        $this->setTextLinesTopBottomPadding(15);
        $this->setImageQuality(100);
        $this->setSaveBackup(false);
    }

    public function generate(): void
    {
        $this->validateParams();

        //if need backup source
        if($this->isSaveBackup() && !copy($this->getSourceImagePath(), $this->backupImagePath)) {
            throw new RuntimeException('Backup creation is failed.');
        }

        // Create Image From Existing File
        $jpgImage = $this->createImageResourceFromExistingFile();

        /**
         * @var array $imageSize - размер изображения
         * [0] - width
         * [1] - height
         */
        $imageSize = getimagesize($this->sourceImagePath);
        /** @var float $leftRightPadding левый и правый отступы текста внутри картинки */
        $leftRightPadding = $imageSize[0] / $this->getCoefficientLeftRightTextPadding();

        if ($this->getTextFontSize() === null) {
            //calculate optimal font size
            $this->setTextFontSize($this->calculateOptimalFontSize($imageSize[1]));
        }

        /** @var float $heightOneLineText высота одной строки текста */
        $heightOneLineText = self::ONE_PUNCT_IN_PIXELS * $this->getTextFontSize();

        // Allocate A Color For The Text
        $white = imagecolorallocate($jpgImage, $this->textColor->red, $this->textColor->green, $this->textColor->blue);

        $fontPath = $this->getFontPath();

        $text = $this->getText();

        $imageWidthWithoutPadings = $imageSize[0] - $leftRightPadding * 2;

        $widthOneLineText = $this->calculateOneLineText($text, $this->getTextFontSize(), $fontPath);

        /**
         * If text too long we split text by whitespace and getting length of each part.
         * Compare sum of length of each text lines and width of image(including paddings)
         */
        if ($widthOneLineText > $imageWidthWithoutPadings) {
            /**
             * Make array of strings which will be long enough for image width
             */
            $wordCounter = 0;
            /** @var array $resultLines result split text with lines-strings long enough for picture width */
            $resultLines = [];
            $currentLine = 0;
            while ($text !== '') {
                //remove whitespaces at the beginning and end
                $text = trim($text);
                //got line, which fit by width and put it to array
                $resultLines[$currentLine] = $this->getTextLine(
                    $text,
                    $imageWidthWithoutPadings,
                    $this->getTextFontSize(),
                    $fontPath
                );
                //remove string from source text
                $text = str_replace($resultLines[$currentLine], '', $text);
                ++$currentLine;
            }
            //how much lines we got after text split
            $numberOfLines = count($resultLines);

            //margins between lines
            /**
             * 1 Calculate height for string as it uppercase
             * 2. Calculate strings top/bottom margins
             */
            $heightMultiLineText = $numberOfLines * $heightOneLineText + $this->getTextLinesTopBottomPadding()
                * ($numberOfLines - 1);

            // try to find left upper coordinate where we start to put strings
            $x = $leftRightPadding;
            $y = $imageSize[1] / 2 - $heightMultiLineText / 2 + $heightOneLineText;

            //calculate coordinates of each lines and put it on image
            foreach ($resultLines as $resultLine) {
                imagettftext($jpgImage, $this->getTextFontSize(), 0, $x, $y, $white, $fontPath, $resultLine);
                $y += $heightOneLineText + $this->getTextLinesTopBottomPadding();
            }
        } else {
            /**
             * One string and it long enough. Calculate left lower corner of rectangle with the text
             */
            $x = $imageSize[0] / 2 - $widthOneLineText / 2;
            $y = $imageSize[1] / 2 + $heightOneLineText / 2;

            // Print Text On Image
            imagettftext($jpgImage, $this->getTextFontSize(), 0, $x, $y, $white, $fontPath, $text);
        }

        $this->saveImageToFile($jpgImage);
    }

    /**
     * @param resource $image
     */
    private function saveImageToFile($image): void
    {
        $mime = mime_content_type($this->getSourceImagePath());

        switch ($mime) {
            case 'image/png':
                $quality = round($this->getImageQuality()/10);
                if (imagepng($image, $this->getResultImagePath(), $quality) === false) {
                    throw new RuntimeException('Can\'t save ' . $mime . 'image to ' . $this->getResultImagePath() . ' with quality ' . $quality);
                }
                break;
            case 'image/jpg':
            case 'image/jpeg':
                if (imagejpeg($image, $this->getResultImagePath(), $this->getImageQuality()) === false) {
                    throw new RuntimeException('Can\'t save ' . $mime . 'image to ' . $this->getResultImagePath());
                }
                break;
            case 'image/gif':
                if (imagegif($image, $this->getResultImagePath()) === false) {
                    throw new RuntimeException('Can\'t save ' . $mime . 'image to ' . $this->getResultImagePath());
                }
                break;
            case 'image/bmp':
                if (imagebmp($image, $this->getResultImagePath()) === false) {
                    throw new RuntimeException('Can\'t save ' . $mime . 'image to ' . $this->getResultImagePath());
                }
                break;
            default:
               throw new RuntimeException('Can\'t save ' . $mime . 'image to ' . $this->getResultImagePath());
        }
        // Clear Memory
        imagedestroy($image);
    }

    /**
     * @throws RuntimeException
     */
    private function validateParams(): void
    {
        if (empty($this->getSourceImagePath())) {
            throw new RuntimeException('Source image path is not set');
        }

        if (empty($this->getText())) {
            throw new RuntimeException('Text is not set');
        }

        if (empty($this->backupImagePath) && $this->isSaveBackup()) {
            throw new RuntimeException('Backup file is not set');
        }
//
//        if ($this->getCoefficientLeftRightTextPadding() === null) {
//            throw new RuntimeException('Coefficient Left and Right Text Padding is not set');
//        }
//
//        if (empty($this->getFontPath())) {
//            throw new RuntimeException('Font path is not set');
//        }
//
//        if ($this->getTextLinesTopBottomPadding() === null) {
//            throw new RuntimeException('Text Lines Top Bottom Padding is not set');
//        }
    }

    /**
     * Find first part of text which can be located on image(long enough but no more than $imageWidthWithoutPadings)
     *
     * @param string $text                      text what need to locate on image
     * @param int    $imageWidthWithoutPaddings width of image without paddings
     * @param int    $textFontSize              text font size
     * @param string $fontPath                  path ot font
     *
     * @return string
     */
    private function getTextLine(
        string $text,
        int $imageWidthWithoutPaddings,
        int $textFontSize,
        string $fontPath
    ): string
    {
        // queue of words. Collect words and then take one by one and check is there enough place for them or not
        $resultLine = [];
        $curText = '';
        $previousText = '';

        //array of words
        $wordsList = explode(' ', $text);

        //if text has only one word
        if (count($wordsList) === 1) {
            return $text;
        }

        // try to find maximum width of text fits to place text
        do {
            $previousText = $curText;
            //if length of string still small but we run out of words
            if (count($wordsList) === 0) {
                break;
            }

            $resultLine[] = array_shift($wordsList);
            $curText = implode(' ', $resultLine);
        } while ($this->calculateOneLineText($curText, $textFontSize, $fontPath) < $imageWidthWithoutPaddings);

        return $previousText;
    }

    /**
     * @param string $text
     * @param int    $textFontSize
     * @param string $fontPath
     *
     * @return int
     */
    private function calculateOneLineText(string $text, int $textFontSize, string $fontPath): int
    {
        /**
         * @var array $bbox create frame for text. so we have 8 coordinates of the frame
         * 0    lower left corner, X
         * 1    lower left corner, Y
         * 2    lower right corner, X
         * 3    lower right corner, Y
         * 4    upper right corner, X
         * 5    upper right corner, Y
         * 6    upper left corner, X
         * 7    upper left corner, Y
         */
        $bbox = imagettfbbox($textFontSize, 0, $fontPath, $text);

        /** @var int $widthOneLineText length of text if we make it in one line */
        return abs($bbox[2] - $bbox[0]);
    }

    /**
     * Create Image From Existing File
     *
     * @return resource
     */
    private function createImageResourceFromExistingFile()
    {
        $sourceImagePath = $this->getSourceImagePath();
        if (file_exists($sourceImagePath === false)) {
            throw new RuntimeException(sprintf('Source file doesnt exist: %s', $sourceImagePath));
        }

        $mime = mime_content_type($sourceImagePath);
        switch ($mime) {
            case 'image/png':
                $image = imagecreatefrompng($sourceImagePath);
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourceImagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourceImagePath);
                break;
            case 'image/bmp':
                $image = imagecreatefrombmp($sourceImagePath);
                break;
            default:
                throw new RuntimeException("Can't create image resource from unknown mimetype file " . $sourceImagePath);
        }

        if ($image === false) {
            throw new RuntimeException("Can't create image resource from file " . $sourceImagePath);
        }

        return $image;
    }


    /**
     * @return int
     */
    public function getCoefficientLeftRightTextPadding(): ?int
    {
        return $this->coefficientLeftRightTextPadding;
    }

    /**
     * @param int $coefficientLeftRightTextPadding
     *
     * @return ImageGenerator
     */
    public function setCoefficientLeftRightTextPadding(int $coefficientLeftRightTextPadding = 20): ImageGenerator
    {
        $this->coefficientLeftRightTextPadding = $coefficientLeftRightTextPadding;

        return $this;
    }

    /**
     * @return int
     */
    public function getTextLinesTopBottomPadding(): ?int
    {
        return $this->textLinesTopBottomPadding;
    }

    /**
     * @param int $textLinesTopBottomPadding
     *
     * @return ImageGenerator
     */
    public function setTextLinesTopBottomPadding(int $textLinesTopBottomPadding = 15): ImageGenerator
    {
        $this->textLinesTopBottomPadding = $textLinesTopBottomPadding;

        return $this;
    }

    /**
     * @return int
     */
    public function getImageQuality(): int
    {
        return $this->imageQuality;
    }

    /**
     * @param int $imageQuality
     *
     * @return ImageGenerator
     */
    public function setImageQuality(int $imageQuality = 100): ImageGenerator
    {
        $this->imageQuality = $imageQuality;

        return $this;
    }

    /**
     * @param int $textFontSize
     *
     * @return ImageGenerator
     */
    public function setTextFontSize(int $textFontSize = 25): ImageGenerator
    {
        $this->textFontSize = $textFontSize;

        return $this;
    }

    /**
     * @param string $fontPath
     *
     * @return ImageGenerator
     */
    public function setFontPath(string $fontPath): ImageGenerator
    {
        $this->fontPath = $fontPath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceImagePath(): ?string
    {
        return $this->sourceImagePath;
    }

    /**
     * @param Color $textColor
     *
     * @return ImageGenerator
     */
    public function setTextColor(Color $textColor): ImageGenerator
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * @param string $resultImage
     *
     * @return ImageGenerator
     */
    public function setResultImagePath(string $resultImage): self
    {
        $this->resultImagePath = $resultImage;

        return $this;
    }

    /**
     * @param string $sourceImage
     *
     * @return $this
     */
    public function setSourceImagePath(string $sourceImage): self
    {
        if (file_exists($sourceImage) === false) {
            throw new RuntimeException(sprintf('Source file doesnt exist: %s', $sourceImage));
        }

        $this->sourceImagePath = $sourceImage;
        //if user does not set specific path for result image we will use source dir
        $this->resultImagePath = dirname($sourceImage) . '/result-' . basename($sourceImage);

        return $this;
    }

    /**
     * @return string
     */
    public function getResultImagePath(): ?string
    {
        return $this->resultImagePath;
    }

    /**
     * @return int
     */
    public function getTextFontSize(): ?int
    {
        return $this->textFontSize;
    }

    /**
     * @return string
     */
    public function getFontPath(): ?string
    {
        return $this->fontPath;
    }

    /**
     * @return Color
     */
    public function getTextColor(): Color
    {
        return $this->textColor;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return ImageGenerator
     */
    public function setText(string $text): ImageGenerator
    {
        $text = str_replace(['«', '»'], '"', $text);

        $this->text = mb_strtoupper($text);

        return $this;
    }

    /**
     * @param int $imageHeight
     *
     * @return float
     */
    protected function calculateOptimalFontSize(int $imageHeight): float
    {
        //coefficient ro reduce text font size
        $coefficient = 11;

        if (mb_strlen($this->getText()) > 36) {
            $coefficient = 13;
        }

        return ($imageHeight / $coefficient) / self::ONE_PUNCT_IN_PIXELS;
    }

    /**
     * @return bool
     */
    public function isSaveBackup(): bool
    {
        return $this->saveBackup;
    }

    /**
     * @param bool $saveBackup
     *
     * @return ImageGenerator
     */
    public function setSaveBackup(bool $saveBackup): ImageGenerator
    {
        $this->saveBackup = $saveBackup;
        if ($saveBackup === true) {
            $sourceImagePath = $this->getSourceImagePath();
            $this->setBackupImagePath(dirname($sourceImagePath) . '/backup/' . basename($sourceImagePath));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getBackupImagePath(): string
    {
        return $this->backupImagePath;
    }

    /**
     * @param string $backupImagePath
     */
    public function setBackupImagePath(string $backupImagePath): void
    {
        $this->backupImagePath = $backupImagePath;
    }
}
