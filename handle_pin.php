<?php

require_once("_constants.php");

class Pin_Handler {
    
    public $txid = null;

    function handle_pin() {

        $this->lg("Starting a PIN check");
        $pin = strtoupper($this->clean_text($_POST['pin']));
        if (empty($pin)) {
            echo json_encode(['error' => 'No PIN was submitted!']);
            die();
        }

        $this->validate_ip();
        $catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
        if (empty($catalog)) {
            $catalog = [];
        }
        
        $map = isset($catalog[$pin]) ? $catalog[$pin] : null;
        if (!$map) {
            echo json_encode(['error' => 'Sorry, I couldn\'t find a map with that PIN']);
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

    function lg($string) {
        if ($this->txid == null) {
            $this->txid = rand(10000,99999);
        }
        $time = date("F d Y H:i:s", time());
        file_put_contents(LOG_FILE, $time . " " . $this->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }

    function validate_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $filename = 'p' . str_replace(".", "", $ip) . 'p';
        $ip_check_file = IPS_FOLDER . $filename;
        if (file_exists($ip_check_file) && (time() - filemtime($ip_check_file)) < 120) {
            $this->lg("IP " . $ip . " is trying PINs too fast");
            echo json_encode(['error' => 'Hold on a minute before you try another PIN']);
            die();
        }
        file_put_contents($ip_check_file, ":)");
    }
}

//Let's make it look like we're working
sleep(2);

$handler = new Pin_Handler();
$handler->handle_pin();
