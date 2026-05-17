<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

if (array_key_exists("PROJECT_ALLOWED_MAPINFO_PROPERTIES", $_POST)) {
    $properties_array = [];
    $properties_string = $_POST["PROJECT_ALLOWED_MAPINFO_PROPERTIES"];
    $properties_string_lines = explode("\n", $properties_string);
    foreach ($properties_string_lines as $line) {
        $line = trim($line);
        $properties_array[] = $line;
    }
    $_POST['PROJECT_ALLOWED_MAPINFO_PROPERTIES'] = $properties_array;
}

$json_settings = json_encode($_POST);
file_put_contents(SETTINGS_FILE, $json_settings);

echo(json_encode(['success' => true]));