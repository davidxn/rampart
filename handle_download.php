<?php
set_time_limit(6000);

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/build_numberer.php");

$path = get_project_full_path();
$numberer = new Build_Numberer();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($path));
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header('Content-Disposition: attachment; filename="'. $numberer->get_current_build_filename() .'"');

$fp = fopen($path, 'rb');
fpassthru($fp);
