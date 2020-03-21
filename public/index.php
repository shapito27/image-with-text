<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use App\Services\ImageGenerator;
use App\Models\Color;

$resultImagePath = __DIR__ .'/images/result/law2.jpg';
$imageGenerator = new ImageGenerator();
$result = $imageGenerator
    ->setSourceImagePath(__DIR__ .'/images/source/law.jpg')
    ->setResultImagePath($resultImagePath)
    ->setFontPath('font/merriweatherregular.ttf')
    ->setTextColor(new Color(255,255,255))
    ->setTextFontSize(25)
    ->setText('Взыскание долга')//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
    ->setCoeficientLeftRightTextPadding(20)
    ->setTextLinesTopBottomPadding(15)
    ->setImageQuality(100)
    ->generate();
echo '<img src="images/result/law2.jpg">';
