<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');

$catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
if (empty($catalog)) {
    $catalog = [];
}

$map_data_table = [];
$map_types = ['UDMF' => 0, 'Hexen' => 0, 'Vanilla/Boom' => 0];

foreach ($catalog as $map_data) {
    $map_file_name = get_source_wad_file_name($map_data['map_number']);
    $my_data['map_file_name'] = $map_file_name;
    $source_wad = UPLOADS_FOLDER . $map_file_name;
    $wad_handler = new Wad_Handler($source_wad, false);
    if ($wad_handler->get_lump('TEXTMAP')) {
        $my_data['map_type'] = 'UDMF';
    } else if ($wad_handler->get_lump('BEHAVIOR')) {
        $my_data['map_type'] = 'Hexen';
    } else {
        $my_data['map_type'] = 'Vanilla/Boom';
    }
    $map_types[$my_data['map_type']]++;
    $map_data_table[$map_data['map_number']] = $my_data;
}

$table_string = "<table class=\"maps_table\"><thead><tr><th>Map File</th><th>Guessed Type</th></tr></thead><tbody>";
foreach($map_data_table as $file_data) {
        $table_string .= "<tr>";
        $table_string .= "<td>" . $file_data['map_file_name'] . "</td>";
        $table_string .= "<td>" . $file_data['map_type'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

?>
<h3>Further Map Data</h3>

<?=$table_string?>
<?php
print_r($map_types);

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
