<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

header("Content-Type: text/plain");

$catalog = new Catalog_Handler();
$credits_table = [];

foreach ($catalog->get_catalog() as $map_data) {
    $author = strtoupper($map_data->author);
    if (!isset($credits_table[$author])) {
        $credits_table[$author] = [];
    }
    $credits_table[$author][] = $map_data->name;
}

ksort($credits_table);
foreach ($credits_table as $author => $maps) {
    print($author . PHP_EOL);
    usort($maps, 'strnatcasecmp');
    foreach ($maps as $map) {
        print("  " . $map . PHP_EOL);
    }
    print (PHP_EOL);
}
