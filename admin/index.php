<?
require_once('header.php');
?>
<h3>Secret Admin Tools</h3>

<img src="/davidn.png" class="inlineimg"/>

<p><a href="/handle_pk3_update.php?nocache=true">Regenerate PK3</a></p>
<p><a href="./generateguide.php">Make guide conversation script</a></p>
<p><a href="./editcatalogue.php">Edit catalog.json</a></p>

<div style="clear: both;">&nbsp;</div>

<div class="code">
<? include_once('work/pk3generation.log'); ?>
</div>

<?
require_once('footer.php');
?>
