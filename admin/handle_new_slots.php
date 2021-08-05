<?php

require_once("_constants.php");
require_once("_functions.php");
require_once("scripts/catalog_handler.php");
require_once("scripts/pin_managers.php");
require_once("scripts/logger.php");

$slots = $_POST['slots'];

if (!is_numeric($slots) || $slots < 1 || $slots > 50) {
    echo(json_encode(['success' => 'false', 'error' => 'Invalid number of slots']));
    die();
}

$catalog_handler = new Catalog_Handler();
$pin_manager = get_setting("PIN_MANAGER_CLASS");
for ($i = 0; $i < $slots; $i++) {
    $pin = $pin_manager::get_new_pin();
    $map_number = $catalog_handler->get_next_available_slot();
    $map_lumpname = "MAP" . (substr("0" . $map_number, -2));
    $location = UPLOADS_FOLDER . get_source_wad_file_name($map_number);
    if (file_exists($location)) {
        unlink($location);
    }
    copy(BLANK_MAP, $location);
    $catalog_handler->update_map_properties(
        $pin,
        [
            'map_name' => 'Map ID ' . $map_number,
            'author' => 'Anonymous',
            'map_number' => $map_number,
            'lumpname' => $map_lumpname,
            'jumpcrouch' => 0,
            'wip' => 0
        ]
    );
}

echo(json_encode(['success' => 'true']));
