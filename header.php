<?php
require_once('_constants.php');
?>
<!doctype html>
<html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="/upload.js?xcache=8" type="text/javascript"></script>
        <link rel="stylesheet" href="/ramp.css?xcache=7">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Exo">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <title>RAMP</title>
    </head>
    <body>
        <!--<div style="position: fixed; z-index: -99; width: 100%; height: 100%">
  <iframe frameborder="0" height="100%" width="100%"
    src="https://www.youtube.com/embed/80NbTTyVLqU?autoplay=1&mute=1&controls=0&rel=0&loop=1&playlist=80NbTTyVLqU&modestbranding=1&origin=http://ramp.teamouse.net">
  </iframe>
</div>-->
        <div class="outer">
            <div class="header">
                <div class="titlebox"><a class="quickstart" href="/rampstarterpack.zip"><img src="/quickstartbutton.png"/></a></div>
                <div class="bannermessage">Fixing maps up and getting ready for open beta!</div>
                <div class="menubox">
                    <a href="/index.php"><div class="menuitem">Home</div></a>
                    <a href="/rules.php"><div class="menuitem">Rules</div></a>
                    <a href="/guide.php"><div class="menuitem">Getting Started</div></a>
<?php
$update_verb = "Submit";
if (ALLOW_NEW_UPLOADS == false) {
    $update_verb = "Update";
}
?>
                    <a href="/upload.php"><div class="menuitem"><?=$update_verb?> a Map</div></a>
                    <a href="/download.php"><div class="menuitem">Download the Project</div></a>
                    <a target="_blank" href="https://discord.gg/afFGdGNhW2"><div class="menuitem">Discord</div></a>
                </div>
            </div>
            <div class="mainbox">