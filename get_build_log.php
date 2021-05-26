<?

$log = @file_get_contents(SINGLE_LOG_FILE);
$mtime = @filemtime(SINGLE_LOG_FILE);
echo json_encode(['log' => $log, 'filetime' => $mtime]);