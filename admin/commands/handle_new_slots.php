<?php

$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'includes/classes/pin_managers.php');

$slots = $_POST['slots'];
$template_name = $_POST['template'] ?? '';

if (!is_numeric($slots) || $slots < 1 || $slots > 50) {
    echo(json_encode(['success' => 'false', 'error' => 'Invalid number of slots']));
    die();
}

//If we've been given a template file, use those to create our links
if ($template_name) {
$template_file = '../data/' . $template_name . '.links';
$template_entries = [];
    if (is_file($template_file)) {
        $template_entries = file($template_file);
    }
}

$catalog_handler = new Catalog_Handler();
$pin_manager = get_setting("PIN_MANAGER_CLASS");
for ($i = 0; $i < $slots; $i++) {
    $pin = $pin_manager::get_new_pin();
    $map_number = $catalog_handler->get_next_available_slot();
    $next_map_number = $map_number + 1;
    //Default map properties...
    if ($map_number < 10) {
        $map_number = "0" . $map_number;
    }
    if ($next_map_number < 10) {
        $next_map_number = "0" . $next_map_number;
    }
    $map_lumpname = "MAP" . $map_number;
    $mapinfo = "next = " . "MAP" . $next_map_number;
    
    //...but override them if we're using a template
    if (isset($template_entries[$i])) {
        $template_line = explode(" ", $template_entries[$i]);
        $map_lumpname = $template_line[0];
        $template_next = $template_line[1];
        $template_secret_next = $template_line[2];
        $mapinfo = "next = " . $template_next . "\n" .
                   "secretnext = " . $template_secret_next;
    }
    
    @mkdir(UPLOADS_FOLDER);
    $location = UPLOADS_FOLDER . get_source_wad_file_name($map_number);
    if (file_exists($location)) {
        unlink($location);
    }
    copy(BLANK_MAP, $location);
    $catalog_handler->update_map_properties(
        $map_number,
        [
            'name' => 'Map ' . $map_number,
            'author' => 'Nobody yet',
            'mapnum' => $map_number,
            'lump' => $map_lumpname,
            'jumpCrouch' => 0,
            'wip' => 1,
            'mapInfoString' => $mapinfo,
            'pin' => $pin,
            'rampId' => $map_number
        ]
    );
}

echo(json_encode(['success' => 'true']));
