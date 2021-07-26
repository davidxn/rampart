<?php
require_once('_constants.php');
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
    $row_data = [
        'pin' => $identifier,
        'map_name' => $map_data['map_name'],
        'lumpname' => isset($map_data['lumpname']) ? $map_data['lumpname'] : ("MAP" . substr("0" . $map_data['map_number'], -2)),
        'author' => $map_data['author'],
        'updated' => filemtime(UPLOADS_FOLDER . "MAP" . $map_data['map_number'] . ".WAD"),
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
   return $a['map_number'] > $b['map_number']; 
});

$table_string = "<table class=\"maps_table\"><thead><tr><th>Map</th><th>PIN</th><th>Lump</th><th>Name</th><th>Author</th><th>Special</th><th>Updated</th><th></th><th></th></tr></thead><tbody>";
foreach($file_table as $file_data) {
        $table_string .= "<tr>";
        $table_string .= "<td>" . $file_data['map_number'] . "</td>";
        $table_string .= "<td>" . $file_data['pin'] . "</td>";
        $table_string .= "<td name=\"" . $file_data['pin'] . "\" class=\"editable-property\">";
        $table_string .= "<div class=\"property-editor\"><input name=\"lumpname\" value=\"" . $file_data['lumpname'] . "\"></input><button class=\"property-ok\"/><button class=\"property-cancel\"/></div>";
        $table_string .= "<span class=\"property-edit\">" . $file_data['lumpname'] . "</span>";
        $table_string .= "</td>";
        $table_string .= "<td>" . $file_data['map_name'] . "</td>";
        $table_string .= "<td>" . $file_data['author'] . "</td>";
        $table_string .= "<td>";
        if ($file_data['jumpcrouch']) {
            $table_string .= '<img src="/img/special_jump.png"/>';
        }
        if ($file_data['wip']) {
            $table_string .= '<img src="/img/special_wip.png"/>';
        }
        if ($file_data['locked']) {
            $table_string .= '<img src="/img/special_locked.png"/>';
        }  
        $table_string .= "</td>";
        $table_string .= "<td>" . date("F j, Y, g:i a T", $file_data['updated']) . "</td>";
        if ($file_data['locked']) {
            $table_string .= '<td><a href="mapstatus.php?action=unlock&pin=' . $file_data['pin'] . '">Unlock</a></td>';
        } else {
            $table_string .= '<td><a href="mapstatus.php?action=lock&pin=' . $file_data['pin'] . '">Lock</a></td>';

        }
        $table_string .= '<td><a href="downloadmap.php?mapnum=' . $file_data['map_number'] . '">Download</a></td>';
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

?>
                <p>New uploads are currently <?=(ALLOW_NEW_UPLOADS ? 'ALLOWED' : 'LOCKED')?></p>
                <p>Edits are currently <?=(ALLOW_EDIT_UPLOADS ? 'ALLOWED' : 'LOCKED')?></p>
                
                <p>Total maps: <?=$total_maps?></p>
                <p>WIP maps: <?=$wip_maps?></p>
                <p>Locked maps: <?=$locked_maps?></p>
                
                <?=$table_string?>
                <script src="admin.js" type="text/javascript"></script>
<?php
require_once('footer.php');

