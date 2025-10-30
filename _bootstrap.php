<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');

spl_autoload_register(function ($class) {
    require_once 'scripts/' . strtolower($class) . '.php';
});