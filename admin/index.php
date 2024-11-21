<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>Secret Admin Tools</h3>

<div class="lightbox">
<p><a href="./settings.php">Edit project settings</a></p>
<p><a href="./mapstatus.php">Edit map slots</a></p>
<p><a href="./handle_settings_touch.php">Force new snapshot generation on next download</a></p>
<p><a href="../handle_pk3_update.php?nozip=1&nocache=1">Compile project without producing output file</a></p>
<p><a href="./makecredits.php">Create map credits text</a></p>
</div>
<div style="clear: both;">&nbsp;</div>

<div id="chartContainer" style="height: 300px">&nbsp;</div>

<?php
 
$dataPoints = ['Preparation' => [], 'Map compiling' => [], 'Copying static content' => [], 'Generating hub resources' => [], 'Writing project file' => []];

$lines = "";
exec("tail " . PK3_GEN_HISTORY_FILE, $lines);
foreach ($lines as $line) {
    $linedata = explode(",", $line);
    $date = new DateTime();
    $date->setTimestamp($linedata[0]);
    $label = $date->format("M d H:i:s");
    $dataPoints['Preparation'][] = ["y" => $linedata[1], "label" => $label];
    //Weird case here is for when the third linedata element was for file size
    $dataPoints['Map compiling'][] = ["y" => isset($linedata[2]) && $linedata[2] < 10000 ? $linedata[2] - $linedata[1] : 0, "label" => $label];
    $dataPoints['Copying static content'][] = ["y" => isset($linedata[3]) ? $linedata[3] - $linedata[2] : 0, "label" => $label];
    $dataPoints['Generating hub resources'][] = ["y" => isset($linedata[4]) && $linedata[4] < 10000 ? $linedata[4] - $linedata[3] : 0, "label" => $label];
    $dataPoints['Writing project file'][] = ["y" => isset($linedata[5]) ? $linedata[5] - $linedata[4] : 0, "label" => $label];
}
 
?>
<script>
window.onload = function() {

CanvasJS.addColorSet("customColorSet1", ["#55F", "#66F", "#77F", "#88F", "#99F"]);
var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
    colorSet: "customColorSet1",
    backgroundColor: "#333",
	title:{
		text: "Recent Build Times",
        fontColor: "white",
        fontFamily: "Exo"
	},
	axisY: {
		title: "Build Time",
		includeZero: true,
        labelFontColor: "white",
        labelFontFamily: "Exo"
	},
	axisX: {
        labelFontColor: "white",
        labelFontFamily: "Exo"
	},
	data: [
<?php
	foreach ($dataPoints as $index => $points) {
	echo '{
		type: "stackedBar",
		name: "' . $index . '",
		yValueFormatString: "#s",
		indexLabel: "{y}",
		indexLabelPlacement: "inside",
		indexLabelFontWeight: "bolder",
		indexLabelFontColor: "white",
        indexLabelFontFamily: "Exo",
		dataPoints:' . json_encode($points, JSON_NUMERIC_CHECK) . '
		},' . PHP_EOL;
	}
?>
	]
});
chart.render();
 
}
</script>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

<div class="code">
<?php if (is_file(PK3_GEN_LOG_FILE)) { include_once(PK3_GEN_LOG_FILE); } ?>
</div>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

