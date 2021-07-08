<?
require_once('_constants.php');
require_once('header.php');
?>
<h3>Secret Admin Tools</h3>

<img src="/davidn.png" class="inlineimg"/>

<p><a href="/handle_pk3_update.php?nocache=true">Regenerate PK3</a></p>
<p><a href="./generateguide.php">Show guide conversation script</a></p>
<p><a href="./makecredits.php">Show credits</a></p>
<p><a href="./editcatalogue.php">Edit catalog.json</a></p>

<div style="clear: both;">&nbsp;</div>

<div class="code">
<? include_once(PK3_GEN_LOG_FILE); ?>
</div>

<?
require_once('footer.php');
?>
