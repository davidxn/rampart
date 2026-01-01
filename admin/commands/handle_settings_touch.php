<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

# Touch settings file
touch(SETTINGS_FILE);
echo(json_encode(['success' => true]));