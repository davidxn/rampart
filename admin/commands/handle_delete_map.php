<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

$catalog = new Catalog_Handler();
$ramp_id = $_POST['rampid'] ?? null;

if ($ramp_id == null || !$catalog->get_map_by_ramp_id($ramp_id)) {
    echo(json_encode(['success' => false, 'error' => "Map $ramp_id not found"]));
    die();
}

$catalog->delete_map($ramp_id);
echo(json_encode(['success' => true]));
die();