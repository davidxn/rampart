<?php
require_once($SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');

$constants_file = explode("\n", file_get_contents("../_constants.php")); //Use relative path here! Can't rely on constants file being OK
$constants_table = '<table class="map_table">';
$line_number = 0;
foreach($constants_file as $line) {
    if (empty($line) || $line == "<?") { continue; }
    $elements = explode("=", $line);
    $const_name = trim(substr($elements[0], 6));
    $const_value = trim($elements[1]);
    if ($const_name == "RAMPART_HOME") { continue; }
    $constants_table .= "<tr><td>" . $const_name . "</td><td><input name=\"constant" . $line_number . "\" value=\"" . $const_value . "\"/></td></tr>" . PHP_EOL;
    $line_number++;
}
$constants_table .= "</table>";

?>
<h3>Project Options</h3>

<?=$constants_table?>

<?php
require_once($SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
