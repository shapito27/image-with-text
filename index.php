<?php
//Set the Content Type
header('Content-type: image/jpeg');

$imagePath = './images/source/law_min.jpg';
$resultImagePath = './images/result/law_min.jpg';
// Create Image From Existing File
$jpgImage = imagecreatefromjpeg($imagePath);

const COEFICENT_LEFT_RIGHT_TEXT_PADDING = 20;
/** @var float 1 punct of font size */
const ONE_PUNCT_IN_PIXELS = 1.338;
const TEXT_FONT_SIZE = 25;
/**
 * Text color red green blue
 */
const TEXT_COLOR_RED = 255;
const TEXT_COLOR_GREEN = 255;
const TEXT_COLOR_BLUE = 255;

const TEXT_LINES_TOP_BOTTOM_PADDING = 15;// по умолчанию сделать процент от шрифта, но также дать возможность менять

/**
 * @var array $getimagesize - размер изображения
 * [0] - width
 * [1] - height
 */
$getimagesize = getimagesize($imagePath);
/** @var float $leftRightPadding левый и правый отступы текста внутри картинки */
$leftRightPadding = $getimagesize[0] / COEFICENT_LEFT_RIGHT_TEXT_PADDING;




/** @var float $heightOneLineText высота одной строки текста */
$heightOneLineText = ONE_PUNCT_IN_PIXELS * TEXT_FONT_SIZE;
//die();

// Allocate A Color For The Text
$white = imagecolorallocate($jpgImage, TEXT_COLOR_RED, TEXT_COLOR_GREEN, TEXT_COLOR_BLUE);

// Set Path to Font File
$fontPath = 'font/merriweatherregular.ttf';

// Set Text to Be Printed On Image
$text = 'Почему важно вовремя обратиться к опытному адвокату';//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
$text = mb_strtoupper($text);

$imageWidthWithoutPadings = $getimagesize[0] - $leftRightPadding * 2;
/**
 * Найти первую подходящую по размеру(найболее длинную , но не больше $imageWidthWithoutPadings) часть текста для размещения на картинке
 * @param string $text текст, где будем искать
 * @param int $imageWidthWithoutPadings ширина картинки с вычето отспупов.
 * @param int $textFontSize размер шрифта
 * @param string $fontPath путь к шрифту
 * @return string
 */
function getTextLine(string $text, int $imageWidthWithoutPadings, int $textFontSize, string $fontPath): string
{
    // сюда копим словам вытаскивая по одному из начала текста, потом проверяем поместятся или нет
    $resultLine = [];
    $curText = '';
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
        $curText = implode(' ', $resultLine);
    } while (calculateOneLineText($curText, $textFontSize, $fontPath) < $imageWidthWithoutPadings);

    return $previousText;
}

/**
 * @param string $text
 * @param int $textFontSize
 * @param string $fontPath
 * @return int
 */
function calculateOneLineText(string $text, int $textFontSize, string $fontPath): int
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

$widthOneLineText = calculateOneLineText($text, TEXT_FONT_SIZE, $fontPath);

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
    $curentLine = 0;
    while ($text !== '') {
        //очищаем от пробелов по бокам
        $text = trim($text);
        //получаем строчку, которая поместится по ширине, сохраняем в масси
        $resultLines[$curentLine] = getTextLine($text, $imageWidthWithoutPadings, TEXT_FONT_SIZE, $fontPath);
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
    $heightMultiLineText = $numberOfLines * $heightOneLineText + TEXT_LINES_TOP_BOTTOM_PADDING * ($numberOfLines - 1);

    // находим левый верхнюю точку откуда начинать вставлять строчки
    $x = $leftRightPadding;
    $y = $getimagesize[1] / 2 - $heightMultiLineText / 2 + $heightOneLineText;

    //рассчитываем координаты каждой строчки и выводим
    foreach ($resultLines as $resultLine) {
        imagettftext($jpgImage, TEXT_FONT_SIZE, 0, $x, $y, $white, $fontPath, $resultLine);
        $y += $heightOneLineText + TEXT_LINES_TOP_BOTTOM_PADDING;
    }
} else {
    /**
     * Одна строка и она умещается. Считаем левый нижний угол прямоугольника с текстом
     */
    $x = $getimagesize[0] / 2 - $widthOneLineText / 2;
    $y = $getimagesize[1] / 2 + $heightOneLineText / 2;

    // Print Text On Image
    imagettftext($jpgImage, TEXT_FONT_SIZE, 0, $x, $y, $white, $fontPath, $text);
}

// Send Image to Browser
imagejpeg($jpgImage, $resultImagePath);

// Clear Memory
imagedestroy($jpgImage);