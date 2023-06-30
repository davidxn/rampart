<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');
$catalog_handler = new Catalog_Handler();

$build_info_string = file_get_contents(BUILD_INFO_FILE);
if (!$build_info_string) {
    die();
}
$build_info = json_decode($build_info_string, true);

$successful_dnums = $build_info['custom_defined_doomednums'];
ksort($successful_dnums);

$rejected_dnums = $build_info['rejected_doomednums'];
ksort($rejected_dnums);
?>

<?php if ($rejected_dnums) { ?>
<p>Rejected DoomEdNums:</p>
<?php
$table_string = "<table class=\"maps_table\"><thead><tr><th>DoomEdNum</th><th>Class</th><th>Rejected from map</th><th>By author</th><th>Defined in map</th><th>By author</th></tr></thead><tbody>";
foreach($rejected_dnums as $rejected_dnum_info) {
      
        $original_map_data = $catalog_handler->get_map_by_number($rejected_dnum_info['original_definer']);
        $rejected_map_data = $catalog_handler->get_map_by_number($rejected_dnum_info['failed_definer']);
        $table_string .= "<tr>";
        $table_string .= "<td class=\"nopad\">" . $rejected_dnum_info['dnum'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $rejected_dnum_info['classname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $rejected_map_data['lumpname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $rejected_map_data['author'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $original_map_data['lumpname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $original_map_data['author'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

echo ($table_string);

} ?>
                <p>Used DoomEdNums:</p>
<?php
$table_string = "<table class=\"maps_table\"><thead><tr><th>DoomEdNum</th><th>Class</th><th>Lump Name</th><th>Map Author</th></tr></thead><tbody>";
foreach($successful_dnums as $successful_dnum => $dnum_info) {
      
        $map_data = $catalog_handler->get_map_by_number($dnum_info['map_number']);
        $table_string .= "<tr>";
        $table_string .= "<td class=\"nopad\">" . $successful_dnum . "</td>";
        $table_string .= "<td class=\"nopad\">" . $dnum_info['classname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $map_data['lumpname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $map_data['author'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

echo ($table_string);

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');

