<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');

$date_pk3 = @filemtime(get_project_full_path());
$date_catalog = @filemtime(CATALOG_FILE);

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
foreach($catalog_handler->get_catalog() as $ramp_id => $map_data) {
    $wad_path = UPLOADS_FOLDER . get_source_wad_file_name($map_data->rampId);
    $row_data = [
        'ramp_id' => $ramp_id,
        'pin' => $map_data->pin,
        'map_name' => $map_data->name,
        'lumpname' => $map_data->lump,
        'mapinfo' => $map_data->mapInfoString ?? '',
        'author' => $map_data->author,
        'updated' => file_exists($wad_path) ? date("Y-m-d g:i", filemtime($wad_path)) : "(No file)",
        'map_number' => $map_data->mapnum,
        'jumpcrouch' => $map_data->jumpCrouch ?? 0,
        'wip' => $map_data->wip ?? 0,
        'locked' => $map_data->locked ?? 0,
        'disabled' => $map_data->disabled ?? 0
    ];
    $file_table[] = $row_data;
    if ($row_data['locked']) { $locked_maps++; }
    if ($row_data['wip']) { $wip_maps++; }
    $total_maps++;
}

usort($file_table, 'compare_lumpnamed_things');

$table_string = "<table class=\"maps_table\"><thead><tr><th>Ramp ID</th><th>Mapnum</th><th>Lump</th><th>PIN</th><th>Name</th><th>Author</th>";
if (get_setting("PROJECT_WRITE_MAPINFO")) {
    $table_string .= "<th>MAPINFO</th>";
}
$table_string .= "<th>Updated</th><th style=\"min-width: 90px\"></th></tr></thead><tbody>";
foreach($file_table as $file_data) {
        $table_string .= '<tr name="' . $file_data['ramp_id'] . '">';
        $table_string .= "<td>" . $file_data['ramp_id'] . "</td>";
        $table_string .= html_property_editor('mapnum', $file_data['map_number']);
        $table_string .= html_property_editor('lump', $file_data['lumpname']);
        $table_string .= html_property_editor('pin', $file_data['pin']);
        $table_string .= html_property_editor('name', $file_data['map_name']);
        $table_string .= html_property_editor('author', $file_data['author']);
        if (get_setting("PROJECT_WRITE_MAPINFO")) {
            $table_string .= html_property_editor('mapInfoString', $file_data['mapinfo'], 'textarea');
        }

        $table_string .= "<td>" . $file_data['updated'] . "</td>";
        $table_string .= '<td class="editable-property">';
        if ($file_data['locked']) {
            $table_string .= '<button class="property property-locked"></button>';
        } else {
            $table_string .= '<button class="property property-unlocked"></button>';
        }
        $table_string .= '&nbsp;' . Logger::get_log_link($file_data['map_number']);
        $table_string .= '&nbsp;<a href="downloadmap.php?rampid=' . $file_data['ramp_id'] . '"><button class="property property-download"></button></a>';
        $table_string .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        
        if ($file_data['disabled'] > 0) {
            $table_string .= '<button class="property property-disabled"></button>';
        } else {
            $table_string .= '<button class="property property-enabled"></button>';
        }
        $table_string .= '<a href="deletemap.php?rampid=' . $file_data['ramp_id'] . '" onclick="return confirm(\'Are you sure you want to delete map: ' . $file_data['map_name'] . '?\')"><button class="property property-delete"></button></a>';
        $table_string .= '</td>';
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

function html_property_editor($property_name, $current_value, $type = 'text') {
        $table_string = '';
        $table_string .= "<td class=\"editable-property\"><div class=\"property-editor\">";
        if ($type == 'text') {
            $table_string .= "<input name=\"" . $property_name . "\" value=\"" . $current_value . "\"></input>";
        } else if ($type == 'textarea') {
            $table_string .= "<textarea class=\"code minitextarea\" name=\"" . $property_name . "\">" . $current_value . "</textarea>";
        }
        $table_string .= "<br/><button class=\"property property-ok\"></button><span>&nbsp;</span><button class=\"property property-cancel\"></button></div>";
        if ($type == 'text') {
            $table_string .= "<span class=\"property-edit\">" . $current_value . "</span>";
        } else if ($type == 'textarea') {
            $table_string .= "<span class=\"property-edit\"><pre class=\"code minitextarea\">" . $current_value . "</textarea></span>";
        }
        
        $table_string .= "</td>";
        return $table_string;
}

?>
                <p>Creating new map slots is currently <?=(get_setting("ALLOW_NEW_UPLOADS") ? 'ALLOWED' : 'LOCKED')?></p>
                <p>Updates are currently <?=(get_setting("ALLOW_EDIT_UPLOADS") ? 'ALLOWED' : 'LOCKED')?></p>
                
                <p>Total maps: <?=$total_maps?></p>
                <p>WIP maps: <?=$wip_maps?></p>
                <p>Locked maps: <?=$locked_maps?></p>
                
                <?php if ($total_maps == 0) {
                    echo('<p>No map slots are in the project yet.</p>');
                }
                else {
                    echo($table_string);
                }
                ?>
                
                <div class="lightbox">
                
                <?php if ($total_maps == 0) { ?>
                <button class="template-slots" name="udoom" data-slots="36">Create map slots for Ultimate Doom</button>
                <button class="template-slots" name="doom2" data-slots="32">Create map slots for Doom 2</button>
                <p>or...</p>
                <?php } ?>
                
                <p>Add <input class="number-of-slots" style="width: 50px" type="number" value="1"></input> more map slots <button class="new-slots">Add slots</button></p>
                </div>
                <script src="admin.js?xcache=9" type="text/javascript"></script>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

