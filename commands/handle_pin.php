<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "includes/classes/pin_managers.php");

class Pin_Handler {

    function handle_pin(): void {

        Logger::lg("Starting a PIN check");
        $pin = strtoupper(clean_text($_POST['pin']));
        if (empty($pin)) {
            echo json_encode(['error' => 'No PIN was submitted!']);
            die();
        }

        $this->validate_ip();
        $catalog_handler = new Catalog_Handler();
        
        $map = $catalog_handler->get_map_by_pin($pin);
        if (!$map) {
            $pin_manager = get_setting("PIN_MANAGER_CLASS");
            $provisional_pin_accepted = (new $pin_manager())->consume_provisional_pin($pin);
            if (!$provisional_pin_accepted) {
                echo json_encode(['error' => "Sorry, I couldn't find a map with that PIN"]);
                die();
            }
            $this->create_map_slot($catalog_handler, $pin);
            $map = $catalog_handler->get_map_by_pin($pin);
        }
        $locked = $catalog_handler->is_map_locked($map->rampId);
        if ($locked) {
            echo json_encode(['error' => 'This map is locked for edits! Contact the project owner if you need to update it.']);
            die();
        }

        echo json_encode([
            "name" => $map->name,
            "author" => $map->author,
            "musiccredit" => $map->musicCredit ?? '',
            "flags" => $map->flags,
            "category" => $map->category ?? 0,
            "length" => $map->length ?? 0,
            "difficulty" => $map->difficulty ?? 0,
            "monsters" => $map->monsterCount ?? 0,
        ]);
    }

    function validate_ip() {
        if (is_ip_banned()) {
            echo json_encode(['error' => "Sorry, I couldn't find a map with that PIN"]);
            die();
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $filename = 'p' . str_replace(".", "", $ip) . 'p';
        $ip_check_file = IPS_FOLDER . $filename;
        @mkdir(IPS_FOLDER);
        if (file_exists($ip_check_file) && (time() - filemtime($ip_check_file)) < get_setting("PIN_ATTEMPT_GAP")) {
            Logger::lg("IP " . $ip . " is trying PINs too fast");
            echo json_encode(['error' => 'Hold on a minute before you try another PIN']);
            die();
        }
        file_put_contents($ip_check_file, ":)");
    }

    function create_map_slot(Catalog_Handler $catalog_handler, string $pin): void {
        $ramp_id = $catalog_handler->get_next_available_slot();
        $map_lump = ($ramp_id < 10 ? ('MAP0' . $ramp_id) : ('MAP' . $ramp_id));
        $map_number = $ramp_id;

        @mkdir(UPLOADS_FOLDER);
        $location = UPLOADS_FOLDER . get_source_wad_file_name($ramp_id);
        if (file_exists($location)) {
            unlink($location);
        }
        copy(BLANK_MAP, $location);
        $catalog_handler->update_map_properties(
            $ramp_id,
            [
                'name' => 'New Map ' . $ramp_id,
                'author' => '',
                'mapnum' => $ramp_id,
                'lump' => $map_lump,
                'flags' => [],
                'wip' => 1,
                'pin' => $pin,
                'rampId' => $map_number
            ]
        );
    }
}

//Let's make it look like we're working (quick spam discourager)
sleep(2);

$handler = new Pin_Handler();
$handler->handle_pin();
