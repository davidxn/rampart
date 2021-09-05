<?php
require_once('_constants.php');
if (!file_exists(SETTINGS_FILE) && !$GLOBALS['SKIP_SETTINGS_CHECK']) {
    header('Location: /admin/settings.php');
    die();
}
?>
<!doctype html>
<html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="/upload.js?xcache=8" type="text/javascript"></script>
        <link rel="stylesheet" href="/ramp.css?xcache=8">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Exo">
        <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <title>RAMPART - <?=get_setting("PROJECT_TITLE")?></title>
    </head>
    <body>
        <div class="outer">
            <div class="header">
                <div class="titlebox">&nbsp;</div>
<?php
if (!is_writable(WORK_FOLDER)) {
?>
                <div class="errormessage">The work folder doesn't seem to be writable! Check its location in _constants.php</div>
<?php  
}
if (!empty(get_setting("BANNER_MESSAGE"))) {
?>
                <div class="bannermessage"><?=get_setting("BANNER_MESSAGE")?></div>
<?php  
}
?>
                <div class="menubox">
                    <a href="/index.php"><div class="menuitem">Home</div></a>
                    <a href="/rules.php"><div class="menuitem">Rules</div></a>
                    <a href="/guide.php"><div class="menuitem">Getting Started</div></a>
<?php
$update_verb = "Submit";
if (!get_setting("ALLOW_NEW_UPLOADS")) {
    $update_verb = "Upload";
}
if (get_setting("ALLOW_NEW_UPLOADS") || get_setting("ALLOW_EDIT_UPLOADS")) {
?>
                    <a href="/upload.php"><div class="menuitem"><?=$update_verb?> a Map</div></a>
<?php
}
?>
                    <a href="/download.php"><div class="menuitem">Download</div></a>
                </div>
            </div>
            <div class="mainbox">
