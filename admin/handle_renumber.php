<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/catalog_handler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');


class Catalog_Renumberer {
    
    function renumber() {
        $catalog = new Catalog_Handler();
        $catalog->renumber();
    }
}

$handler = new Catalog_Renumberer();
$handler->renumber();
echo json_encode(['success' => true]);