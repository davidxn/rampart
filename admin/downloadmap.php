<?php
set_time_limit(6000);

require_once("_constants.php");

if (!is_numeric($_GET['mapnum'])) {
    die("Not a valid map number");
}

$path = UPLOADS_FOLDER . "MAP" . $_GET['mapnum'] . ".WAD";
if (!file_exists($path)) {
    die("No map file found");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $path);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($path));
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. "MAP" . $_GET['mapnum'] . ".WAD" .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);
