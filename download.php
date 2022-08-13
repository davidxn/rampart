<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/catalog_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

$date_pk3 = @filemtime(get_project_full_path()) or 0;
$date_catalog = @filemtime(CATALOG_FILE) or 0;

$catalog_handler = new Catalog_Handler();
$file_table = [];
foreach($catalog_handler->get_catalog() as $identifier => $map_data) {
    $map_number = $map_data['map_number'];
    $wad_path = UPLOADS_FOLDER . get_source_wad_file_name($map_number);

    $file_table[] = [
        'pin' => $identifier,
        'map_name' => $map_data['map_name'],
        'lumpname' => $map_data['lumpname'],
        'author' => $map_data['author'],
        'updated' => file_exists($wad_path) ? date("Y-m-d g:i", filemtime($wad_path)) : "(No file)",
        'map_number' => $map_number,
        'jumpcrouch' => isset($map_data['jumpcrouch']) ? $map_data['jumpcrouch'] : 0,
        'wip' => isset($map_data['wip']) ? $map_data['wip'] : 0,
        'locked' => isset($map_data['locked']) ? $map_data['locked'] : 0,
        'log_link' => Logger::get_log_link($map_number),
    ];
}

usort($file_table, function($a, $b) {
    if (substr($a['lumpname'], 0, 3) == "MAP" && substr($b['lumpname'], 0, 3) == "MAP") {
        return (substr($a['lumpname'], 3)) > (substr($b['lumpname'], 3));
    }
    return $a['lumpname'] > $b['lumpname'];
});

$table_string = "<table class=\"maps_table\"><thead><tr><th>Map</th><th>Name</th><th>Author</th><th>Special</th><th>Updated</th><th>Log</th></tr></thead><tbody>";
foreach($file_table as $file_data) {
      
        $table_string .= "<tr>";
        $table_string .= "<td>" . $file_data['lumpname'] . "</td>";
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
        $table_string .= "<td>" . $file_data['updated'] . "</td>";
        $table_string .= "<td>" . $file_data['log_link'] . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

?>
                <p>You can generate and download a snapshot version of the project here. Snapshots will use whatever resources have been added to the project by contributors, and aren't guaranteed to be stable.</p>

                <p>There are <?=count($file_table)?> maps in the project.</p>

                <center><button type="button" id="download_button">Download a snapshot version!</button>
                <p class="smallnote" id="download_status">&nbsp;</p></center>
                
                <p>Map catalogue updated <?=$date_catalog ? " at " . date("F j, Y, g:i a T", $date_catalog) : "(never updated)"?></p>
                
                <?=$table_string?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . './footer.php');

