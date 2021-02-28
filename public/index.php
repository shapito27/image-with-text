<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Shapito27\ImageCreator\Services\ImageGenerator;
use Shapito27\ImageCreator\Models\Color;

var_dump('$_POST', $_POST);
var_dump('$_FILES', $_FILES);
$savedFile = [];
const RESULT_IMAGE_PATH = '/images/result/';
if (!empty($_FILES) && isset($_POST['submit'])) {
    try {
        print_r($_FILES['image']);
        $savedFile = saveFile($_FILES['image']);
    } catch (Exception $exception) {
        print_r(var_export($exception, true));
        print_r($exception);
        exit;
    }
    var_dump($savedFile);
}
var_dump('end');
?>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="image">Image <input type="file" id="image" name="image"></label>
        <br>
        <label for="text">Text <input type="text" name="text" id="text"></label>
        <br>
        Text settings
        <br>
        <label for="text-size">Size <input type="number" name="text-size" id="text-size" value="22"></label>
        <br>
        <label for="text-color">Color <input type="color" name="text-color" id="text-color" value="#000000"></label>
        <br>
        <input name="submit" type="submit" value="Generate">
    </form>
<?php
if (!empty($_POST['text']) && !empty($savedFile)) {
    $resultImagePath     = RESULT_IMAGE_PATH.$savedFile['name'];
    $resultImageFullPath = __DIR__. $resultImagePath;
    $imageGenerator      = new ImageGenerator();
    $result              = $imageGenerator
        ->setSourceImagePath($savedFile['path'])
        ->setResultImagePath($resultImageFullPath)
        ->setFontPath('font/merriweatherregular.ttf')
        ->setTextColor(new Color($_POST['text-color']))
        ->setTextFontSize($_POST['text-size'])
        ->setText($_POST['text'])//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
        ->setCoeficientLeftRightTextPadding(20)
        ->setTextLinesTopBottomPadding(15)
        ->setImageQuality(100)
        ->generate();
    echo '<img src="'.$resultImagePath.'">';
}

function saveFile(array $file)
{
//var_dump(555);
    $target_dir = __DIR__.'/images/source/';
    $sourceFileName   = basename($file["name"]);
    $sourceFile   = $target_dir.$sourceFileName;
    $imageFileType = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
    $newFileName   = basename($file["tmp_name"]) . '.'.$imageFileType;
    $resultFile   = $target_dir.$newFileName;
    var_dump('$imageFileType', $imageFileType);
// Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        throw new RuntimeException('File is not an image');
    }

// Check if file already exists
    if (file_exists($resultFile)) {
        throw new RuntimeException('Sorry, file '.$resultFile.' already exists.');
    }

// Check file size
    if ($file["size"] > 500000) {
        throw new RuntimeException('File size more than 500KB');
    }

// Allow certain file formats
    if ($imageFileType !== "jpg" && $imageFileType !== "png" && $imageFileType !== "jpeg"
        && $imageFileType !== "gif") {
        throw new RuntimeException(
            'Sorry, only JPG, JPEG, PNG & GIF files are allowed. File is gotten '.$imageFileType
        );
    }

// Check if $uploadOk is set to 0 by an error
    if (!move_uploaded_file($file["tmp_name"], $resultFile)) {
        throw new RuntimeException(            'Sorry, there was an error uploading your file.'        );
    }

    return [
        'name' => $newFileName,
        'path' => $resultFile,
    ];
}
