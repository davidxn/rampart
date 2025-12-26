<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

# Rewrite entire settings file

$json_settings = json_encode($_POST);
file_put_contents(SETTINGS_FILE, $json_settings);

echo(json_encode(['success' => true]));