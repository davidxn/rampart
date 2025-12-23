<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');

$date_pk3 = @filemtime(get_project_full_path()) or 0;
$date_catalog = @filemtime(CATALOG_FILE) or 0;

$catalog_handler = new Catalog_Handler();
$file_table = [];
foreach ($catalog_handler->get_catalog() as $rampId => $rampMap) {
    $map_number = $rampMap->mapnum;
    $wad_path = UPLOADS_FOLDER . get_source_wad_file_name($rampId);

    $file_table[] = [
        'pin' => $rampMap->pin,
        'map_name' => $rampMap->name,
        'lumpname' => $rampMap->lump,
        'author' => $rampMap->author,
        'updated' => file_exists($wad_path) ? date("Y-m-d g:i", filemtime($wad_path)) : "(No file)",
        'map_number' => $map_number,
        'jumpcrouch' => $rampMap->jumpCrouch,
        'wip' => $rampMap->wip,
        'locked' => $rampMap->locked,
        'log_link' => Logger::get_log_link($rampId),
        'length' => $rampMap->length,
        'difficulty' => $rampMap->difficulty,
        'category' => $rampMap->category
    ];
}

usort($file_table, function($a, $b) {
    if (substr($a['lumpname'], 0, 3) == "MAP" && substr($b['lumpname'], 0, 3) == "MAP") {
        return intval(substr($a['lumpname'], 3)) <=> intval(substr($b['lumpname'], 3));
    }
    return $a['lumpname'] <=> $b['lumpname'];
});

$table_string = "<table class=\"maps_table\"><thead><tr><th>Map</th><th>Name</th><th>Author</th><th>Details</th><th>Category</th><th>Updated</th><th>Log</th></tr></thead><tbody>";
foreach($file_table as $file_data) {
      
        $table_string .= "<tr>";
        $table_string .= "<td>" . $file_data['lumpname'] . "</td>";
        $table_string .= "<td>" . $file_data['map_name'] . "</td>";
        $table_string .= "<td>" . $file_data['author'] . "</td>";
        $table_string .= "<td>";
        if ($file_data['wip']) {
            $table_string .= ' <span class="map_property">WIP</span>';
        }
        if ($file_data['locked']) {
            $table_string .= '<img src="/img/special_locked.png"/>';
        }
        if ($file_data['length']) {
            $table_string .= ' <span class="map_length">' . $file_data['length'] . '</span>';
        }
        if ($file_data['difficulty']) {
            $table_string .= ' <span class="map_difficulty">' . $file_data['difficulty'] . '</span>';
        }
        if ($file_data['jumpcrouch']) {
            $table_string .= ' <span class="map_property">Jump</span>';
        }      
        $table_string .= "</td>";
        $table_string .= "<td>" . $file_data['category'] . "</td>";
        $table_string .= "<td>" . $file_data['updated'] . "</td>";
        $table_string .= "<td>" . $file_data['log_link'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

if (get_setting("ALLOW_SNAPSHOT_DOWNLOAD")) {
?>
                <p>You can generate and download a snapshot version of the project here. Snapshots will use whatever resources have been added to the project by contributors, and aren't guaranteed to be stable.</p>

                <p>There are <?=count($file_table)?> maps in the project.</p>

                <center><button type="button" id="download_button">Download a snapshot version!<br/>Runs on <?=get_setting("DOOM_VERSION")?></button>
                <p class="smallnote" id="download_status">&nbsp;</p></center>
                
                <p>Map catalogue updated <?=$date_catalog ? " at " . date("F j, Y, g:i a T", $date_catalog) : "(never updated)"?></p>
<?php } ?>
                <?=$table_string?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');

