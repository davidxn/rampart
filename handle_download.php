<?
set_time_limit(6000);

require_once("_constants.php");

$path = PK3_FILE;

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $path);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($path));
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. PROJECT_FILE_NAME .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);