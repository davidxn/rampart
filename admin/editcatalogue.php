<?
require_once('header.php');
require_once('_constants.php');
?>
<h3>Edit catalog.json</h3>

<div style="clear: both;">&nbsp;</div>

<textarea class="code">
<?
$catalog = file_get_contents("../" . CATALOG_FILE);
$catalog = str_replace("},", "}," . PHP_EOL, $catalog);
echo($catalog);
?>
</textarea>

<?
require_once('footer.php');
?>
