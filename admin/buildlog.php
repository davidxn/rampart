<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<h3>Latest build log</h3>

<div class="code">
<?php 
if (is_file(PK3_GEN_LOG_FILE)) {
    include_once(PK3_GEN_LOG_FILE);
} else {
    echo ("No log file found");
} ?>
</div>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');

