<?php
require_once('_constants.php');
require_once('_functions.php');
require_once('header.php');
require_once('scripts/catalog_handler.php');

$date_pk3 = @filemtime(PK3_FILE) or 0;
$date_catalog = @filemtime(CATALOG_FILE) or 0;

$catalog_handler = new Catalog_Handler();
$file_table = [];

if (isset($_GET['action']) && $_GET['action'] == 'lock') {
    $catalog_handler->lock_map($_GET['pin']);
}
if (isset($_GET['action']) && $_GET['action'] == 'unlock') {
    $catalog_handler->unlock_map($_GET['pin']);
}

$total_maps = 0;
$locked_maps = 0;
$wip_maps = 0;
foreach($catalog_handler->get_catalog() as $identifier => $map_data) {
    $wad_path = UPLOADS_FOLDER . get_source_wad_file_name($map_data['map_number']);
    $row_data = [
        'pin' => $identifier,
        'map_name' => $map_data['map_name'],
        'lumpname' => isset($map_data['lumpname']) ? $map_data['lumpname'] : ("MAP" . substr("0" . $map_data['map_number'], -2)),
        'author' => $map_data['author'],
        'updated' => file_exists($wad_path) ? date("Y-m-d g:i", filemtime($wad_path)) : "(No file)",
        'map_number' => $map_data['map_number'],
        'jumpcrouch' => isset($map_data['jumpcrouch']) ? $map_data['jumpcrouch'] : 0,
        'wip' => isset($map_data['wip']) ? $map_data['wip'] : 0,
        'locked' => isset($map_data['locked']) ? $map_data['locked'] : 0
    ];
    $file_table[] = $row_data;
    if ($row_data['locked']) { $locked_maps++; }
    if ($row_data['wip']) { $wip_maps++; }
    $total_maps++;
}

usort($file_table, function($a, $b) {
   return $a['lumpname'] > $b['lumpname']; 
});

$table_string = "<table class=\"maps_table\"><thead><tr><th>Lump</th><th>ID</th><th>PIN</th><th>Name</th><th>Author</th><th>Special</th><th>Updated</th><th></th></tr></thead><tbody>";
foreach($file_table as $file_data) {
        $table_string .= "<tr>";
        $table_string .= html_property_editor($file_data['pin'], 'lumpname', $file_data['lumpname']);
        $table_string .= "<td>" . $file_data['map_number'] . "</td>";
        $table_string .= "<td>" . $file_data['pin'] . "</td>";
        $table_string .= html_property_editor($file_data['pin'], 'map_name', $file_data['map_name']);
        $table_string .= html_property_editor($file_data['pin'], 'author', $file_data['author']);
        $table_string .= "<td>";
        if ($file_data['jumpcrouch']) {
            $table_string .= '<img src="/img/special_jump.png"/>';
        }
        if ($file_data['wip']) {
            $table_string .= '<img src="/img/special_wip.png"/>';
        } 
        $table_string .= "</td>";
        $table_string .= "<td>" . $file_data['updated'] . "</td>";
        $table_string .= '<td class="editable-property" name="' . $file_data['pin'] . '">';
        if ($file_data['locked']) {
            $table_string .= '<button class="property property-locked"></button>';
        } else {
            $table_string .= '<button class="property property-unlocked"></button>';
        }
        $table_string .= '<a href="downloadmap.php?mapnum=' . $file_data['map_number'] . '"><button class="property property-download"></button></a>';
        $table_string .= '</td>';
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

function html_property_editor($pin, $property_name, $current_value) {
        $table_string = '';
        $table_string .= "<td name=\"" . $pin . "\" class=\"editable-property\">";
        $table_string .= "<div class=\"property-editor\"><input name=\"" . $property_name . "\" value=\"" . $current_value . "\"></input><button class=\"property property-ok\"></button><span>&nbsp;</span><button class=\"property property-cancel\"></button></div>";
        $table_string .= "<span class=\"property-edit\">" . $current_value . "</span>";
        $table_string .= "</td>";
        return $table_string;
}

?>
                <p>Creating new map slots is currently <?=(ALLOW_NEW_UPLOADS ? 'ALLOWED' : 'LOCKED')?></p>
                <p>Updates are currently <?=(ALLOW_EDIT_UPLOADS ? 'ALLOWED' : 'LOCKED')?></p>
                
                <p>Total maps: <?=$total_maps?></p>
                <p>WIP maps: <?=$wip_maps?></p>
                <p>Locked maps: <?=$locked_maps?></p>
                
                <?=$table_string?>
                
                <div class="lightbox"><p>Add <input class="number-of-slots" value="1"></input> more map slots </p><button class="new-slots">Go</button></div>
                <script src="admin.js" type="text/javascript"></script>
<?php
require_once('footer.php');

