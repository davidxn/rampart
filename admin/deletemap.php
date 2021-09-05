<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/catalog_handler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');

$catalog = new Catalog_Handler();
$pin = $_GET['mappin'];

if (!$catalog->get_map($pin)) {
    header("Location: mapstatus.php");
    die();
}

$catalog->delete_map($pin);
header("Location: mapstatus.php");
die();