<?php
function convertToWebp($sourcePath, $destinationPath)
{
    $image = null;

    $imageInfo = getimagesize($sourcePath);
    $mimeType = $imageInfo['mime'];

    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        default:
            throw new Exception("Unsupported image format: $mimeType");
    }

    if ($image) {
        imagewebp($image, $destinationPath, 90);
        imagedestroy($image);
    }
}
