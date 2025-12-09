<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

$catalog = new Catalog_Handler();
$pin = $_GET['mappin'];

if (!$catalog->get_map($pin)) {
    header("Location: mapstatus.php");
    die();
}

$catalog->disable_map($pin);
header("Location: mapstatus.php");
die();