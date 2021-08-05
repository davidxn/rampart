<?php

require_once("_constants.php");

# Rewrite entire settings file

$json_settings = json_encode($_POST);
file_put_contents(SETTINGS_FILE, $json_settings);

echo(json_encode(['success' => true]));