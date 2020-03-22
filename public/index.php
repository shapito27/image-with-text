<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Shapito27\ImageCreator\Services\ImageGenerator;
use Shapito27\ImageCreator\Models\Color;

$resultImagePath     = 'images/result/law2.jpg';
$resultImageFullPath = __DIR__ . DIRECTORY_SEPARATOR . $resultImageFullPath;
$imageGenerator      = new ImageGenerator();
$result              = $imageGenerator
    ->setSourceImagePath(__DIR__ . '/images/source/law.jpg')
    ->setResultImagePath($resultImageFullPath)
    ->setFontPath('font/merriweatherregular.ttf')
    ->setTextColor(new Color(255, 255, 255))
    ->setTextFontSize(25)
    ->setText('Взыскание долга 2')//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
    ->setCoeficientLeftRightTextPadding(20)
    ->setTextLinesTopBottomPadding(15)
    ->setImageQuality(100)
    ->generate();
echo '<img src="' . $resultImagePath . '">';
