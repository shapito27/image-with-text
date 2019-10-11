<?php
//Set the Content Type
header('Content-type: image/jpeg');

$imagePath = './images/source/law_min.jpg';
// Create Image From Existing File
$jpgImage = imagecreatefromjpeg($imagePath);

/** @var array $getimagesize [0] - width [1] - height */
$getimagesize = getimagesize($imagePath);

$leftRightPadding = $getimagesize[0] / 40;


/** @var float $onePunct 1punct of font size */
$onePunct = 1.338;
$textFontSize = 25;

$heightOneLineText = $onePunct * $textFontSize;
//die();

// Allocate A Color For The Text
$white = imagecolorallocate($jpgImage, 255, 255, 255);

// Set Path to Font File
$fontPath = 'font/merriweatherregular.ttf';

// Set Text to Be Printed On Image
$text = "Взыскание долга, неустойки, дебиторской задолженности";//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";

/**
 *
 */
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
var_dump($bbox);

/** @var int $widthOneLineText длина текста в одну строчку */
$widthOneLineText = $bbox[2] - $bbox[0];

$imageWidthWithoutPadings = $getimagesize[0] - $leftRightPadding * 2;

function getLineText()
{

}

if ($widthOneLineText > ($getimagesize[0] - $leftRightPadding * 2)) {
    $wordsList = explode(' ', $text);
    $wordsNumber = count($wordsList);
    $xxx = round(($wordsNumber * $imageWidthWithoutPadings / $widthOneLineText), 0, PHP_ROUND_HALF_DOWN);
    $wordCounter = 0;
    $resultLines = [];
    $curentLine = 0;
    while($wordCounter< $xxx){
        $resultLines[$curentLine] = array_shift($wordsList);
    }
    var_dump($xxx);
//    while()
} else {
    /**
     * Одна строка и она умещается
     */
    $x = $getimagesize[0] / 2 - $widthOneLineText / 2;
    $y = $getimagesize[1] / 2 + $heightOneLineText / 2;
}
// Print Text On Image
imagettftext($jpgImage, $textFontSize, 0, $x, $y, $white, $fontPath, $text);

// Send Image to Browser
imagejpeg($jpgImage, './images/result/law_min.jpg');

// Clear Memory
imagedestroy($jpgImage);