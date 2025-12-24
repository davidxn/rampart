<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

$catalog = new Catalog_Handler();
$ramp_id = $_GET['rampid'];

if (!$catalog->get_map_by_ramp_id($ramp_id)) {
    header("Location: mapstatus.php");
    die();
}

$catalog->delete_map($ramp_id);
header("Location: mapstatus.php");
die();