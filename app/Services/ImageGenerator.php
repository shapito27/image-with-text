<?php

namespace Shapito27\ImageCreator\Services;

use Shapito27\ImageCreator\Models\Color;
use RuntimeException;

/**
 * Class ImageGenerator
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
    private $coeficientLeftRightTextPadding;
    /** @var int */
    private $textLinesTopBottomPadding;// по умолчанию сделать процент от шрифта, но также дать возможность менять
    /** @var int */
    private $imageQuality;

    /** @var float 1 punct of font size */
    public const ONE_PUNCT_IN_PIXELS = 1.338;

    /**
     * @return false|resource
     */
    public function generate()
    {
        $this->validateParams();

        // Create Image From Existing File
        $jpgImage = $this->createImageResourceFromExistingFile();

        /**
         * @var array $imageSize - размер изображения
         * [0] - width
         * [1] - height
         */
        $imageSize = getimagesize($this->sourceImagePath);
        /** @var float $leftRightPadding левый и правый отступы текста внутри картинки */
        $leftRightPadding = $imageSize[0] / $this->getCoeficientLeftRightTextPadding();

        if($this->getTextFontSize() === null) {
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
         * Если текст слишком длинный, то делим текст по пробелам и получаем длинну каждой части
         * Сравниваем длинну текста и пространство( ширина картинки - левый и правый оступы)
         */
        if ($widthOneLineText > $imageWidthWithoutPadings) {
            /**
             * Делаем массив строк, которые поместятся в выделенную ширину
             */
            $wordCounter = 0;
            /** @var array $resultLines текст разбитый на список строк, которые поместятся на картинку */
            $resultLines = [];
            $curentLine  = 0;
            while ($text !== '') {
                //очищаем от пробелов по бокам
                $text = trim($text);
                //получаем строчку, которая поместится по ширине, сохраняем в масси
                $resultLines[$curentLine] = $this->getTextLine(
                    $text,
                    $imageWidthWithoutPadings,
                    $this->getTextFontSize(),
                    $fontPath
                );
                //удаляем из общего текста найденную подстроку
                $text = str_replace($resultLines[$curentLine], '', $text);
                ++$curentLine;
            }
            //сколько получилось строчек после разбивки тектста
            $numberOfLines = count($resultLines);
            //отступ между текстами

            /**
             * 1 Cчитаем высоту текста по большой букве
             * 2. считаем отступы между строк
             */
            $heightMultiLineText = $numberOfLines * $heightOneLineText + $this->getTextLinesTopBottomPadding()
                                                                         * ($numberOfLines - 1);

            // находим левый верхнюю точку откуда начинать вставлять строчки
            $x = $leftRightPadding;
            $y = $imageSize[1] / 2 - $heightMultiLineText / 2 + $heightOneLineText;

            //рассчитываем координаты каждой строчки и выводим
            foreach ($resultLines as $resultLine) {
                imagettftext($jpgImage, $this->getTextFontSize(), 0, $x, $y, $white, $fontPath, $resultLine);
                $y += $heightOneLineText + $this->getTextLinesTopBottomPadding();
            }
        } else {
            /**
             * Одна строка и она умещается. Считаем левый нижний угол прямоугольника с текстом
             */
            $x = $imageSize[0] / 2 - $widthOneLineText / 2;
            $y = $imageSize[1] / 2 + $heightOneLineText / 2;

            // Print Text On Image
            imagettftext($jpgImage, $this->getTextFontSize(), 0, $x, $y, $white, $fontPath, $text);
        }

        $this->saveImageToFile($jpgImage);
    }

    /**
     * @param  resource  $image
     */
    private function saveImageToFile($image): void
    {
        //@todo take care about all formats. Not only jpeg
        imagejpeg($image, $this->getResultImagePath(), $this->getImageQuality());
        // Clear Memory
        imagedestroy($image);
    }

    /**
     * @throws RuntimeException
     */
    private function validateParams()
    {
        if (empty($this->getSourceImagePath())) {
            throw new RuntimeException('Source image path is not set');
        }

        if (empty($this->getResultImagePath())) {
            throw new RuntimeException('Result image path is not set');
        }

        if (empty($this->getText())) {
            throw new RuntimeException('Text is not set');
        }

        if (empty($this->getCoeficientLeftRightTextPadding())) {
            throw new RuntimeException('Coeficient Left and Right Text Padding is not set');
        }

        if (empty($this->getFontPath())) {
            throw new RuntimeException('Text font is not set');
        }

        if (empty($this->getTextLinesTopBottomPadding())) {
            throw new RuntimeException('Text Lines Top Bottom Padding is not set');
        }
    }

    /**
     * Найти первую подходящую по размеру(найболее длинную , но не больше $imageWidthWithoutPadings) часть текста для размещения на картинке
     *
     * @param  string  $text  текст, где будем искать
     * @param  int  $imageWidthWithoutPadings  ширина картинки с вычето отспупов.
     * @param  int  $textFontSize  размер шрифта
     * @param  string  $fontPath  путь к шрифту
     *
     * @return string
     */
    private function getTextLine(
        string $text,
        int $imageWidthWithoutPadings,
        int $textFontSize,
        string $fontPath
    ): string {
        // сюда копим словам вытаскивая по одному из начала текста, потом проверяем поместятся или нет
        $resultLine   = [];
        $curText      = '';
        $previousText = '';
        //массив слов текста
        $wordsList = explode(' ', $text);

        //если в тексте осталось одно слово
        if (count($wordsList) === 1) {
            return $text;
        }

        // находим максимальную ширину текста, чтобы вписалась в пространство
        do {
            $previousText = $curText;
            //если длина строки еще маленькая, а слова в тексте уже закончились
            if (count($wordsList) === 0) {
                break;
            }

            $resultLine[] = array_shift($wordsList);
            $curText      = implode(' ', $resultLine);
        } while ($this->calculateOneLineText($curText, $textFontSize, $fontPath) < $imageWidthWithoutPadings);

        return $previousText;
    }

    /**
     * @param  string  $text
     * @param  int  $textFontSize
     * @param  string  $fontPath
     *
     * @return int
     */
    private function calculateOneLineText(string $text, int $textFontSize, string $fontPath): int
    {
        /**
         * @var array $bbox создаем рамку для текста. по нему имеем 8 координат - каждой точки.
         * 0    нижний левый угол, X координата
         * 1    нижний левый угол, Y координата
         * 2    нижний правый угол, X координата
         * 3    нижний правый угол, Y координата
         * 4    верхний правый угол, X координата
         * 5    верхний правый угол, Y координата
         * 6    верхний левый угол, X координата
         * 7    верхний левый угол, Y координата
         */
        $bbox = imagettfbbox($textFontSize, 0, $fontPath, $text);

        /** @var int $widthOneLineText длина текста в одну строчку */
        return abs($bbox[2] - $bbox[0]);
    }

    /**
     * Create Image From Existing File
     * @return false|resource
     */
    private function createImageResourceFromExistingFile()
    {
        return imagecreatefromjpeg($this->sourceImagePath);
    }


    /**
     * @return int
     */
    public function getCoeficientLeftRightTextPadding(): int
    {
        return $this->coeficientLeftRightTextPadding;
    }

    /**
     * @param  int  $coeficientLeftRightTextPadding
     *
     * @return ImageGenerator
     */
    public function setCoeficientLeftRightTextPadding(int $coeficientLeftRightTextPadding = 20): ImageGenerator
    {
        $this->coeficientLeftRightTextPadding = $coeficientLeftRightTextPadding;

        return $this;
    }

    /**
     * @return int
     */
    public function getTextLinesTopBottomPadding(): int
    {
        return $this->textLinesTopBottomPadding;
    }

    /**
     * @param  int  $textLinesTopBottomPadding
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
     * @param  int  $imageQuality
     *
     * @return ImageGenerator
     */
    public function setImageQuality(int $imageQuality = 100): ImageGenerator
    {
        $this->imageQuality = $imageQuality;

        return $this;
    }

    /**
     * @param  int  $textFontSize
     *
     * @return ImageGenerator
     */
    public function setTextFontSize(int $textFontSize = 25): ImageGenerator
    {
        $this->textFontSize = $textFontSize;

        return $this;
    }

    /**
     * @param  string  $fontPath
     *
     * @return ImageGenerator
     */
    public function setFontPath(string $fontPath): ImageGenerator
    {
        $this->fontPath = $fontPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceImagePath(): string
    {
        return $this->sourceImagePath;
    }

    /**
     * @param  Color  $textColor
     *
     * @return ImageGenerator
     */
    public function setTextColor(Color $textColor): ImageGenerator
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * @param  string  $resultImage
     *
     * @return ImageGenerator
     */
    public function setResultImagePath(string $resultImage): self
    {
        $this->resultImagePath = $resultImage;

        return $this;
    }

    /**
     * @param  string  $sourceImage
     *
     * @return $this
     */
    public function setSourceImagePath(string $sourceImage): self
    {
        $this->sourceImagePath = $sourceImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getResultImagePath(): string
    {
        return $this->resultImagePath;
    }

    /**
     * @return int
     */
    public function getTextFontSize(): int
    {
        return $this->textFontSize;
    }

    /**
     * @return string
     */
    public function getFontPath(): string
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
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param  string  $text
     *
     * @return ImageGenerator
     */
    public function setText(string $text): ImageGenerator
    {
        $this->text = mb_strtoupper($text);

        return $this;
    }

    /**
     * @param  int  $imageHeight
     *
     * @return float
     */
    protected function calculateOptimalFontSize(int $imageHeight):float
    {
        //koeficent ro reduce text font size
        $koeficent = 11;

        if (mb_strlen($this->getText()) > 36) {
            $koeficent = 13;
        }

        return ($imageHeight / $koeficent) / self::ONE_PUNCT_IN_PIXELS;
    }
}