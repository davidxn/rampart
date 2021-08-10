<?php
require_once("_constants.php");
require_once("_functions.php");
require_once("scripts/catalog_handler.php");

$catalog = new Catalog_Handler();
$pin = $_GET['mappin'];

if (!$catalog->get_map($pin)) {
    header("Location: mapstatus.php");
    die();
}

$catalog->delete_map($pin);
header("Location: mapstatus.php");
die();