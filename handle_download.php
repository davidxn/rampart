<?

require_once("./_constants.php");

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. PROJECT_FILE_NAME .'"');
echo @file_get_contents(PK3_FILE);