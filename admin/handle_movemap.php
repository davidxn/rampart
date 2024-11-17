<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/catalog_handler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');

class Map_Mover {
    
    function move($pin, $to) {
        $catalog = new Catalog_Handler();
        return $catalog->move_map($pin, $to);
    }
}

$handler = new Map_Mover();
$result = $handler->move($_POST['pin'], $_POST['to']);
if ($result) {
    echo("Map successfully moved!");
} else {
    echo("Something went wrong, god knows what happened");
}

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'footer.php');