<?php
set_time_limit(6000);

require_once("_constants.php");

$path = get_project_full_path();

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $path);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($path));
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. get_setting("PROJECT_FILE_NAME") .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);
