<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');

$earliest_timestamp = null;
$latest_timestamp = null;

$fn = fopen(LOG_FILE,"r");

$uploads = [];
$map_flags = [];

while(!feof($fn)) {
    $line = fgets($fn);
    if (strpos($line, 'Wrote map') === false) {
        continue;
    }
    $linedatestring = substr($line, 0, strpos($line, 'Wrote') - 7);
    $linemapnumber = str_replace(":", "", substr($line, strpos($line, 'map') + 4, 3));
    $linedate = DateTime::createFromFormat('F d Y H:i:s', $linedatestring);
    $linetimestamp = $linedate->getTimestamp() - ($linedate->getTimestamp() % (24 * 60 * 60));

    $is_first_upload = false;
    if(!isset($uploads[$linetimestamp])) {
        $uploads[$linetimestamp] = [];
    }
    if (!isset($map_flags[$linemapnumber])) {
        $map_flags[$linemapnumber] = true;
        $is_first_upload = true;
    }
    $uploads[$linetimestamp][] = ['map' => $linemapnumber, 'first' => $is_first_upload];
}

fclose($fn);
?>
<div id="maptime">&nbsp;</div>
<table class="mapuploadtable">
<?php
foreach($uploads as $timestamp => $elements) {
    $date = new DateTime();
    $date->setTimestamp($timestamp + (24*60*60)); //Compensate for time zone
    print('<tr><td><b>' . $date->format('F d') . '</b></td>' . PHP_EOL . '<td>');
    foreach ($elements as $element) {
        if (!$element['first']) {
            continue;
        }
        print('<span class="mapsubmission ' . ($element['first'] ? 'newmap' : '') . '">' . $element['map'] . '</span>' . PHP_EOL);
    }
    print('</tr>');
}
?>
</table>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
