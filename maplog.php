<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

if (!is_numeric($_GET['id'])) {
    die();
}
$catalog_handler = new Catalog_Handler();
$map_data = $catalog_handler->get_map_by_number($_GET['id']);

echo("<h3>" . $map_data['map_name'] . "</h3>");
?>

<div class="code">
<?php if (Logger::map_has_log($map_data['map_number'])) { include_once(Logger::get_map_log_file($map_data['map_number'])); } ?>
</div>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
