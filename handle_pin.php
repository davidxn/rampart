<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Pin_Handler {

    function handle_pin(): void {

        Logger::lg("Starting a PIN check");
        $pin = strtoupper($this->clean_text($_POST['pin']));
        if (empty($pin)) {
            echo json_encode(['error' => 'No PIN was submitted!']);
            die();
        }

        $this->validate_ip();
        $catalog_handler = new Catalog_Handler();
        
        $map = $catalog_handler->get_map_by_pin($pin);
        if (!$map) {
            echo json_encode(['error' => "Sorry, I couldn't find a map with that PIN"]);
            die();
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
            "jumpcrouch" => $map->jumpCrouch ?? 0,
            "wip" => $map->wip ?? 0,
            "category" => $map->category ?? 0,
            "length" => $map->length ?? 0,
            "difficulty" => $map->difficulty ?? 0,
            "monsters" => $map->monsterCount ?? 0,
        ]);
    }

    function clean_text($string, $length = 0) {
       $string = trim($string);
       $string = preg_replace('/[^A-Za-z0-9\-\'! ]/', '', $string); // Removes special chars.
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }

    function validate_ip() {
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
}

//Let's make it look like we're working (quick spam discourager)
sleep(2);

$handler = new Pin_Handler();
$handler->handle_pin();
