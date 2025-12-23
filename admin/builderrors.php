<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>Build errors</h3>

<?php

function catalogCmp(RampMap $a, RampMap $b): int
{
    return $a->mapnum <=> $b->mapnum;
}

$catalog_handler = new Catalog_Handler();
$catalog = $catalog_handler->get_catalog();
usort($catalog, "catalogCmp");

foreach ($catalog as $map_data) {
    if (Logger::map_has_errors($map_data->mapnum)) {
        print("<h3>" . $map_data->lump . ": " . $map_data->name);
        print('<div class="code">');
        include_once(Logger::get_map_error_file($map_data->mapnum));
        print('</div></h3>');
    }
}
?>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

