<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

# Remove upload and compile locks
release_lock(LOCK_FILE_UPLOAD);
release_lock(LOCK_FILE_COMPILE);
echo(json_encode(['success' => true]));