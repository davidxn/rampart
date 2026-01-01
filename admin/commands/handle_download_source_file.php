<?php
set_time_limit(6000);
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

if (!is_numeric($_GET['rampid'])) {
    die("Not a valid ramp ID");
}

$path = UPLOADS_FOLDER . get_source_wad_file_name($_GET['rampid']);
if (!file_exists($path)) {
    die("No map file found");
}

$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $path);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($path));
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. get_source_wad_file_name($_GET['rampid']) .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);
