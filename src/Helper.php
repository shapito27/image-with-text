<?php
/*
 * Project:         image-with-text
 * File:            Helper.php
 * Date:            2021-03-24
 */

namespace Shapito27\ImageCreator;

use RuntimeException;

class Helper
{
    /**
     * Save file from $_FILE array
     * @param array  $file
     * @param string $destination Dir where file will be saved
     *
     * @return string[]
     */
    public static function saveFile(array $file, string $destination): array
    {
        $sourceFileName = basename($file["name"]);
        $sourceFile     = $destination.$sourceFileName;
        $imageFileType  = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
        $newFileName    = basename($file["tmp_name"]).'.'.$imageFileType;
        $resultFile     = $destination.$newFileName;
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
            throw new RuntimeException('Sorry, there was an error uploading your file from '
                . $file["tmp_name"] . 'to ' . $resultFile);
        }

        return [
            'name' => $newFileName,
            'path' => $resultFile,
        ];
    }
}
