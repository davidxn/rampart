<?
include_once('./header.php');
include_once('./_constants.php');
?>
<style type="text/css">
.mapuploadtable td {
    font-size: 8px;
}
</style>
<script>
var times = [];
<?

$earliest_timestamp = null;
$latest_timestamp = null;

$fn = fopen("./work/log.log","r");

while(!feof($fn)) {
    $line = fgets($fn);
    if (strpos($line, 'Wrote map') !== false) {
        $linedatestring = substr($line, 0, strpos($line, 'Wrote') - 7);
        $linemapnumber = str_replace(":", "", substr($line, strpos($line, 'map') + 4, 3));
        $linedate = DateTime::createFromFormat('F d Y H:i:s', $linedatestring);
        $linetimestamp = $linedate->getTimestamp() - ($linedate->getTimestamp() % (60 * 60));
        if (!$earliest_timestamp) {
            $earliest_timestamp = $linetimestamp;
        }
        $latest_timestamp = $linetimestamp;
        print('if (!Array.isArray(times[' . $linetimestamp . "])) { times[" . $linetimestamp . "] = []; } times[" . $linetimestamp . "].push(" . $linemapnumber . ");" . PHP_EOL);
    }
}

fclose($fn);
?>

var timestamp = <?=$earliest_timestamp?>;
var latest_timestamp = <?=$latest_timestamp?>;

function advanceTime() {
    pipsToAdd = times[timestamp] ? times[timestamp] : [];
    for (mapnum of pipsToAdd) {
        $('#mapcell' + mapnum).append('⬜');
    }
    dateobj = new Date(timestamp * 1000);
    $('#maptime').text(getReadableDate(dateobj));
    if (timestamp >= latest_timestamp) {
        clearInterval(stopwatch);
    }
    timestamp += 60 * 60;
}

function getReadableDate(dateobj) {
    return dateobj.toDateString() + " " + dateobj.getHours() + ":00";
}

var stopwatch = setInterval(advanceTime, 50);

</script>
<div id="maptime">&nbsp;</div>
<table class="mapuploadtable">
<?

$catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
if (empty($catalog)) {
    $catalog = [];
}

$target_height = ceil(count($catalog)/3);
foreach($catalog as $mapdata) {
    print('<tr><td width="20"><b>' . $mapdata['map_number'] . '</b></td><td width="200" id="mapcell' . $mapdata['map_number'] . '"></td>' . PHP_EOL);
    print('<td width="20"><b>' . ($mapdata['map_number']+$target_height) . '</b></td><td width="200" id="mapcell' . ($mapdata['map_number']+$target_height) . '"></td>' . PHP_EOL);
    print('<td width="20"><b>' . ($mapdata['map_number']+$target_height*2) . '</b></td><td width="200" id="mapcell' . ($mapdata['map_number']+$target_height*2) . '"></td></tr>' . PHP_EOL);

    if ($mapdata['map_number'] == $target_height+9) {
        break;
    }
}
?>
</table>
<?
include_once('./footer.php');
