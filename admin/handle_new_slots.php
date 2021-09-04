<?php

require_once("_constants.php");
require_once("_functions.php");
require_once("scripts/catalog_handler.php");
require_once("scripts/pin_managers.php");
require_once("scripts/logger.php");

$slots = $_POST['slots'];
$template_name = $_POST['template'];

if (!is_numeric($slots) || $slots < 1 || $slots > 50) {
    echo(json_encode(['success' => 'false', 'error' => 'Invalid number of slots']));
    die();
}

//If we've been given a template file, use those to create our links
$template_file = '../data/' . $template_name . '.links';
$template_entries = [];
if (is_file($template_file)) {
    $template_entries = file($template_file);
}

$catalog_handler = new Catalog_Handler();
$pin_manager = get_setting("PIN_MANAGER_CLASS");
for ($i = 0; $i < $slots; $i++) {
    $pin = $pin_manager::get_new_pin();
    $map_number = $catalog_handler->get_next_available_slot();
    
    //Default map properties...
    $map_lumpname = "MAP" . (substr("0" . $map_number, -2));
    $mapinfo = "next = " . "MAP" . (substr("0" . ($map_number+1), -2));
    
    //...but override them if we're using a template
    if (isset($template_entries[$i])) {
        $template_line = split(" ", $template_entries[$i]);
        $map_lumpname = $template_line[0];
        $nextmap = $template_line[1];
        $secretnextmap = $template_line[2];
        $mapinfo = "next = " . $nextmap . "\nsecretnext = " . $secretnextmap;
    }
    
    $location = UPLOADS_FOLDER . get_source_wad_file_name($map_number);
    if (file_exists($location)) {
        unlink($location);
    }
    copy(BLANK_MAP, $location);
    $catalog_handler->update_map_properties(
        $pin,
        [
            'map_name' => 'Map ' . $map_number,
            'author' => 'Nobody yet',
            'map_number' => $map_number,
            'lumpname' => $map_lumpname,
            'jumpcrouch' => 0,
            'wip' => 1,
            'mapinfo' => $mapinfo
        ]
    );
}

echo(json_encode(['success' => 'true']));
