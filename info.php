<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
$catalog_handler = new Catalog_Handler();

if (!file_exists(BUILD_INFO_FILE)) {
    echo ("No build information is available");
    require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');
    die();
}
$build_info_string = file_get_contents(BUILD_INFO_FILE);
if (!$build_info_string) {
    echo ("No build information is available");
    require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');
    die();
}
$build_info = json_decode($build_info_string, true);

$successful_dnums = $build_info['custom_defined_doomednums'];
ksort($successful_dnums);

$rejected_dnums = $build_info['rejected_doomednums'];
ksort($rejected_dnums);

$ambients = $build_info['global_ambient_list'];
ksort($ambients);
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

<?php if ($successful_dnums) { ?>
<p>Used DoomEdNums:</p>
<?php
$table_string = "<table class=\"maps_table\"><thead><tr><th>DoomEdNum</th><th>Class</th><th>Lump Name</th><th>Map Author</th></tr></thead><tbody>";
$highest_num_range_start = -1;
$reserved_range_pointer = 0;
foreach($successful_dnums as $successful_dnum => $dnum_info) {

	while ($successful_dnum >= $highest_num_range_start && $reserved_range_pointer < count(Lump_Guardian::$reserved_doomed_ranges)) {
        $range = Lump_Guardian::$reserved_doomed_ranges[$reserved_range_pointer];
        $rangeDisplay = $range[0] . "-" . $range[1];
        if ($range[0] == $range[1]) {
            $rangeDisplay = $range[0];
        }
		$table_string .= sprintf("<tr><td class=\"reservednumspan\">%s</td><td class=\"reservednumspan\" colspan=\"4\">%s</td></tr>", $rangeDisplay, $range[2]);
		$reserved_range_pointer++;
		$highest_num_range_start = Lump_Guardian::$reserved_doomed_ranges[$reserved_range_pointer][0] ?? 0;

	}
      
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

} ?>

<?php if ($ambients) { ?>
<p>Ambient sounds:</p>
<?php
$table_string = "<table class=\"maps_table\"><thead><tr><th>Ambient ID</th><th>Lump Name</th><th>Map Author</th></tr></thead><tbody>";
foreach($ambients as $index => $ambient_info) {
      
        $map_data = $catalog_handler->get_map_by_number($ambient_info['map']);
        $table_string .= "<tr>";
        $table_string .= "<td class=\"nopad\">" . $index . "</td>";
        $table_string .= "<td class=\"nopad\">" . $map_data['lumpname'] . "</td>";
        $table_string .= "<td class=\"nopad\">" . $map_data['author'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

echo ($table_string);

} ?>

<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');

