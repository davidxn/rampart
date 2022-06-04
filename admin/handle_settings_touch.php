<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');

# Touch settings file
touch(SETTINGS_FILE);
header("Location: index.php");
die();