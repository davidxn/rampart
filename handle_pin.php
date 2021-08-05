<?php

require_once("_constants.php");
require_once("scripts/catalog_handler.php");
require_once("scripts/logger.php");

class Pin_Handler {
    
    public $txid = null;

    function handle_pin() {

        Logger::lg("Starting a PIN check");
        $pin = strtoupper($this->clean_text($_POST['pin']));
        if (empty($pin)) {
            echo json_encode(['error' => 'No PIN was submitted!']);
            die();
        }

        $this->validate_ip();
        $catalog_handler = new Catalog_Handler();
        
        $map = $catalog_handler->get_map($pin);
        if (!$map) {
            echo json_encode(['error' => 'Sorry, I couldn\'t find a map with that PIN']);
            die();
        }
        $locked = $catalog_handler->is_map_locked($pin);
        if ($locked) {
            echo json_encode(['error' => 'This map is locked for edits! Contact the project owner if you need to update it.']);
            die();
        }

        $jumpcrouch = isset($map['jumpcrouch']) ? $map['jumpcrouch'] : 0;
        $wip = isset($map['wip']) ? $map['wip'] : 0;
        echo json_encode([
            "mapname" => $map['map_name'],
            "author" => $map['author'],
            "jumpcrouch" => $jumpcrouch,
            "wip" => $wip
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
