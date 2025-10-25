<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/logger.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>Build errors</h3>

<?php
// Get all maps in the catalogue
// Sort them by map number
//Check if an error file exists for each one
//If it does, output header and contents

function catalogCmp($a, $b) {
    return $a['map_number'] <=> $b['map_number'];
}

$catalog_handler = new Catalog_Handler();
$catalog = $catalog_handler->get_catalog();
usort($catalog, "catalogCmp");

foreach ($catalog as $map_data) {
    if (Logger::map_has_errors($map_data['map_number'])) {
        print("<h3>" . $map_data['lumpname'] . ": " . $map_data['map_name']);
        print('<div class="code">');
        include_once(Logger::get_map_error_file($map_data['map_number']));
        print('</div></h3>');
    }
}
?>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

