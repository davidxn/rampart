<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');
$png_image = imagecreatefrompng(STATIC_CONTENT_FOLDER . "graphics/TITLEPIC.png");

$white = imagecolorallocate($jpg_image, 255, 255, 255);

$font_path = 'font.TTF';

imagestring($png_image, 0, 2, 2, "20210801-1544", imagecolorallocate($png_image, 255, 255, 255));

imagepng($png_image);