<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Shapito27\ImageCreator\Helper;
use Shapito27\ImageCreator\Services\ImageGenerator;
use Shapito27\ImageCreator\Models\Color;

$savedFile = [];
$response = [
    'status' => true,
];
const RESULT_IMAGE_PATH = 'images/result/';

try {
    if (!empty($_FILES)) {
        $savedFile = Helper::saveFile($_FILES['image'], __DIR__ . '/images/source/');
    }
    if (empty($_POST['text'])) {
        throw new RuntimeException('Text is empty');
    }
    if (empty($savedFile)) {
        throw new RuntimeException('Can\'t save file');
    }

    $resultImagePath = RESULT_IMAGE_PATH . $savedFile['name'];
    $resultImageFullPath = __DIR__ . '/' . $resultImagePath;
    $imageGenerator = new ImageGenerator();

    $imageGenerator
        ->setSourceImagePath($savedFile['path'])
        ->setResultImagePath($resultImageFullPath)
        ->setFontPath('font/merriweatherregular.ttf')
        ->setTextColor(new Color($_POST['text-color']))
        ->setTextFontSize((int)$_POST['text-size'])
        ->setText($_POST['text'])
        ->setCoefficientLeftRightTextPadding((int)$_POST['text-coefficient-left-right'])
        ->setTextLinesTopBottomPadding((int)$_POST['text-coefficient-top-bottom'])
        ->setImageQuality((int)$_POST['image-quality'])
        ->generate();

    $response['result'] = $resultImagePath;
} catch (Exception $exception) {
    $response['status'] = false;
    $response['error'] = $exception->getMessage();
}
echo json_encode($response);
exit;
