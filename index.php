<?php
//Set the Content Type
header('Content-type: image/jpeg');

$imagePath = './images/source/law_min.jpg';
// Create Image From Existing File
$jpgImage = imagecreatefromjpeg($imagePath);

$getimagesize = getimagesize($imagePath);
$onePunct = 1.338;
$textFontSize = 25;
var_dump($getimagesize);

//die();

//$centrPoint = $getimagesize;

// Allocate A Color For The Text
$white = imagecolorallocate($jpgImage, 255, 255, 255);

// Set Path to Font File
$fontPath = 'font/merriweatherregular.ttf';

// Set Text to Be Printed On Image
$text = "Взыскание долга, неустойки, дебиторской задолженности";

/**
 * создаем рамку для текста. по нему имеем 8 координат - каждой точки.
 * 1. Рассчитываем, если выходим за рамки, то делим текст по пробелам. Составляем соотношение . Добавляем перенос.
 * 2. Учесть выравнивание по высоте
 */
$bbox = imagettfbbox($textFontSize, 0, $fontPath, $text);

// Print Text On Image
imagettftext($jpgImage, $textFontSize, 0, 75, 300, $white, $fontPath, $text);

// Send Image to Browser
imagejpeg($jpgImage, './images/result/law_min.jpg');

// Clear Memory
imagedestroy($jpgImage);