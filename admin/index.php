<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>Secret Admin Tools</h3>

<div class="lightbox">
<p><a href="./settings.php">Edit project settings</a></p>
<p><a href="./mapstatus.php">Edit map slots</a></p>
<p><a href="/handle_pk3_update.php?nocache=true&redirect=true">Generate new snapshot</a></p>
<p><a href="./makecredits.php">Create map credits text</a></p>
</div>
<div style="clear: both;">&nbsp;</div>

<div id="chartContainer" style="height: 300px">&nbsp;</div>

<?php
 
$dataPoints = [];

$lines = "";
exec("tail " . PK3_GEN_HISTORY_FILE, $lines);
foreach ($lines as $line) {
    $linedata = explode(",", $line);
    $date = new DateTime();
    $date->setTimestamp($linedata[0]);
    $label = $date->format("M d H:i:s");
    $dataPoints[] = ["y" => $linedata[1], "label" => $label];
}
 
?>
<script>
window.onload = function() {

CanvasJS.addColorSet("customColorSet1", ["#77F"]);
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
	data: [{
		type: "bar",
		yValueFormatString: "#s",
		indexLabel: "{y}",
		indexLabelPlacement: "inside",
		indexLabelFontWeight: "bolder",
		indexLabelFontColor: "white",
        indexLabelFontFamily: "Exo",
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	}]
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

