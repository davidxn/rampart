<?php
set_time_limit(6000);

require_once("_constants.php");
require_once("_functions.php");

if (!is_numeric($_GET['mapnum'])) {
    die("Not a valid map number");
}

$path = UPLOADS_FOLDER . get_source_wad_file_name($_GET['mapnum']);
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
header('Content-Disposition: attachment; filename="'. get_source_wad_file_name($_GET['mapnum']) .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);
