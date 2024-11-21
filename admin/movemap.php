<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
?>
<form action="./handle_movemap.php" method="POST">
    <div class="lightbox">
        Be careful with this! If there's a map at the destination map number, it will be overwritten!
    </div>
    <div id="upload-question-pin" style="display: block;">
        <div id="pin_form" class="lightbox">
            <p>Move the map with PIN 
            <input type="text" name="pin" style="width: 100px;" id="move_pin">
             to map slot 
            <input type="number" name="to" style="width: 100px;" id="move_to">
            <button id="confirm_move">Go!</button>
        </div>
    </div>
</form>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');
