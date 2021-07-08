<?
require_once('./_constants.php');
require_once('./header.php');

$date_pk3 = @filemtime(PK3_FILE) or 0;
$date_catalog = @filemtime(CATALOG_FILE) or 0;

$catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
if (!$catalog) {
    $catalog = [];
}
$file_table = [];
foreach($catalog as $identifier => $map_data) {
    $file_table[] = [
        'pin' => $identifier,
        'map_name' => $map_data['map_name'],
        'author' => $map_data['author'],
        'updated' => filemtime(UPLOADS_FOLDER . "MAP" . $map_data['map_number'] . ".WAD"),
        'map_number' => $map_data['map_number'],
        'jumpcrouch' => isset($map_data['jumpcrouch']) ? $map_data['jumpcrouch'] : 0,
        'wip' => isset($map_data['wip']) ? $map_data['wip'] : 0
    ];
}

usort($file_table, function($a, $b) {
   return $a['map_number'] > $b['map_number']; 
});

$table_string = "<table class=\"maps_table\"><thead><tr><th>Map</th><th>Name</th><th>Author</th><th>Special</th><th>Updated</th></tr></thead><tbody>";
foreach($file_table as $file_data) {
        $table_string .= "<tr>";
        $table_string .= "<td>" . $file_data['map_number'] . "</td>";
        $table_string .= "<td>" . $file_data['map_name'] . "</td>";
        $table_string .= "<td>" . $file_data['author'] . "</td>";
        $table_string .= "<td>";
        if ($file_data['jumpcrouch']) {
            $table_string .= '<img src="./special_jump.png"/>';
        }
        if ($file_data['wip']) {
            $table_string .= '<img src="./special_wip.png"/>';
        }            
        $table_string .= "</td>";
        $table_string .= "<td>" . date("F j, Y, g:i a T", $file_data['updated']) . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

?>
                <p>Download a snapshot of the compiled project so far!</p>
                
                <p><?=count($file_table)?> maps have been uploaded and <?=HUB_SLOTS?> slots are currently in the hub. This seems sufficient.</p>
                <?
                if (count($file_table) > HUB_SLOTS) {
                    ?> <p class="smallnote">The hub needs to be expanded so all maps are available!</p> <?
                }
                ?>
                <center><button type="button" id="download_button">Download a snapshot of RAMP!</button>
                <p class="smallnote" id="download_status">&nbsp;</p></center>
                
                <p>Map list updated at <?=$date_catalog ? date("F j, Y, g:i a T", $date_catalog) : "(never updated)"?></p>
                
                <?=$table_string?>
<?
require_once('./footer.php');
?>
