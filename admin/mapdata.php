<?
require_once('header.php');
require_once('_constants.php');
require_once("scripts/wad_handler.php");

<<<<<<< HEAD
$catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
=======
$catalog = @json_decode(file_get_contents(RAMPART_HOME . CATALOG_FILE), true);
>>>>>>> 715c9beefc57e1cc21821b0324eb0bceaaad9f1b
if (empty($catalog)) {
    $catalog = [];
}

$map_data_table = [];
$map_types = ['UDMF' => 0, 'Hexen' => 0, 'Vanilla/Boom' => 0];

foreach ($catalog as $map_data) {
    $map_file_name = "MAP" . $map_data['map_number'] . ".WAD";
    $my_data['map_file_name'] = $map_file_name;
<<<<<<< HEAD
    $source_wad = UPLOADS_FOLDER . $map_file_name;
=======
    $source_wad = RAMPART_HOME . UPLOADS_FOLDER . $map_file_name;
>>>>>>> 715c9beefc57e1cc21821b0324eb0bceaaad9f1b
    $wad_handler = new Wad_Handler($source_wad);
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
<<<<<<< HEAD
<? print_r($map_types);

=======
<? print_r($map_types); ?>




<?
>>>>>>> 715c9beefc57e1cc21821b0324eb0bceaaad9f1b
require_once('footer.php');
