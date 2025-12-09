<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');

?>
<h3>Edit catalog.json</h3>

<div style="clear: both;">&nbsp;</div>

<textarea class="code">
<?php
$catalog = file_get_contents(CATALOG_FILE);
$catalog = str_replace("},", "}," . PHP_EOL, $catalog);
echo($catalog);
?>
</textarea>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

