<?
require_once('./header.php');

$CATALOG_FILE = "./work/catalog.json";
$PK3_FILE = "./work/RAMP-SNAPSHOT.pk3";
$MAPS_FOLDER = "./work/uploads/";

$date_pk3 = @filemtime($PK3_FILE) or 0;
$date_catalog = @filemtime($CATALOG_FILE) or 0;

$catalog = @json_decode(file_get_contents($CATALOG_FILE), true);
if (!$catalog) {
    $catalog = [];
}
$file_table = [];
foreach($catalog as $identifier => $map_data) {
    $file_table[] = [
        'pin' => $identifier,
        'map_name' => $map_data['map_name'],
        'author' => $map_data['author'],
        'updated' => filemtime($MAPS_FOLDER . "MAP" . $map_data['map_number'] . ".WAD"),
        'map_number' => $map_data['map_number'],
        'jumpcrouch' => isset($map_data['jumpcrouch']) ? $map_data['jumpcrouch'] : 0
    ];
}

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
        $table_string .= "</td>";
        $table_string .= "<td>" . date("F j, Y, g:i a T", $file_data['updated']) . "</td>";
        $table_string .= "</tr>";
}
$table_string .= "</tbody></table>";

?>
                <p>Download a snapshot of the compiled project so far!</p>

                <center><button type="button" id="download_button">Download the PK3</button></center>
                
                <p>Map list updated at <?=$date_catalog ? date("F j, Y, g:i a T", $date_catalog) : "(never updated)"?></p>
                
                <?=$table_string?>
                
                <div>This is getting long, isn't it? I moved the download button to the top</div>
<?
require_once('./footer.php');
?>
