<?php
require_once('_constants.php');
header("Content-Type: text/plain");

$catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
if (empty($catalog)) {
    $catalog = [];
}

$credits_table = [];

foreach ($catalog as $map_data) {
    $author = strtoupper($map_data['author']);
    if (!isset($credits_table[$author])) {
        $credits_table[$author] = [];
    }
    $credits_table[$author][] = $map_data['map_name'];
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
