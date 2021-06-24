<?php

require_once("./_constants.php");

class Upload_Handler {

    public $txid = null;

    function handle_upload($filename, $filesize, $tmpname, $pin) {

        $this->lg("Starting an upload attempt");

        $mapname = $this->clean_text($_POST['mapname']);
        $authorname = $this->clean_text($_POST['authorname']);
        $jumpcrouch = $this->clean_text($_POST['jumpcrouch']);
        $wip = 0;
        if (isset($_POST['wip'])) {
            $wip = $this->clean_text($_POST['wip']);
        }
        $pin = strtoupper($this->clean_text($pin));

        $this->validate_fields($mapname, $authorname);
        $this->validate_ip();
        if ($tmpname) {
            $this->validate_header($tmpname);
        }

        //Mutex
        $this->wait_for_lock();

        $this->lg("POST data is: " . print_r($_POST, true));

        $catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
        if (empty($catalog)) {
            $catalog = [];
        }
        
        if ($pin) {
            $existing_map = $this->validate_pin($pin, $catalog);
            $map_number = $existing_map['map_number'];
        }
        else {
            $pin_result = $this->get_new_pin($catalog);
            $map_number = $pin_result['map_number'];
            $pin = $pin_result['pin'];
        }
        
        //Finalize the file, if we have one
        if ($tmpname) {
            $location = UPLOADS_FOLDER . "MAP" . $map_number . ".WAD";
            $this->lg("Moving file " . $tmpname . " to " . $location);
            if (file_exists($location)) {
                unlink($location);
            }
            $result = move_uploaded_file($tmpname, $location);

            if(!$result){
                echo json_encode(['error' => 'Upload error!']);
                die();
            }
        }

        //Now update the catalog
        $catalog[$pin] = [
            'map_name' => $mapname,
            'author' => $authorname,
            'map_number' => $map_number,
            'jumpcrouch' => $jumpcrouch,
            'wip' => $wip
        ];
        file_put_contents(CATALOG_FILE, json_encode($catalog));
        $this->lg("Wrote map " . $map_number . ": " . $pin . " entry to catalog");

        //Unmutex
        unlink(LOCK_FILE_UPLOAD);
        $this->lg("Lock released");

        $success_message = "Success! Your WAD has been added to the project as map MAP" . $map_number . ". Your PIN is <b>" . $pin . "</b> - use this if you ever need to update your level.";

        echo json_encode(["name" => $filename, "size" => $filesize, "pin" => $pin, "map_number" => $map_number, "success" => $success_message]);
    }

    function clean_text($string, $length = 0) {
       $string = trim($string);
       $string = preg_replace('/[^A-Za-z0-9\-\'!: ]/', '', $string); // Removes special chars.
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }

    function get_new_pin($catalog) {
        if (file_exists(PIN_FILE)) {
            $file = file(PIN_FILE);            
        }
        else {
            $file = file(PIN_MASTER_FILE);
        }
        $position = rand(0, count($file)-1);
        $pin = $file[$position];
        unset($file[$position]); //This pops the entry out of the array
        file_put_contents(PIN_FILE, $file);
        $pin = trim($pin);
        $this->lg("Assigning PIN: " . $pin);

        //Now assign a map number by looking at the latest one. Start from 10 so we have some space for defaults in maps 1-9
        //(and to be honest so that I don't have to do a special case adding a 0 to single digit maps)
        $occupied_slots = [];
        foreach($catalog as $mapdata) {
            $occupied_slots[$mapdata['map_number']] = true;
        }
        $examined_slot = FIRST_USER_MAP_NUMBER;
        while (true) {
            if (!isset($occupied_slots[$examined_slot])) {
                break;
            }
            $examined_slot++;
        }        
        $this->lg("Assigning map number: " . $examined_slot);       
        return ['map_number' => $examined_slot, 'pin' => $pin];
    }

    function lg($string) {
        if ($this->txid == null) {
            $this->txid = rand(10000,99999);
        }
        $time = date("F d Y H:i:s", time());
        file_put_contents(LOG_FILE, $time . " " . $this->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }
    
    function validate_pin($pin, $catalog) {
        $map = isset($catalog[$pin]) ? $catalog[$pin] : null;
        if (!$map) {
            echo json_encode(['error' => 'That PIN doesn\'t exist, stop messing with the site please']);
            die();
        }
        return $map;
    }

    function validate_header($filename) {
        $file = fopen($filename,"r");
        $bytes = fread($file, 4);
        fclose($file);
        $match = ($bytes == "PWAD");
        $this->lg("First four bytes " . ($match ? "match" : "do not match") . " PWAD header");
        if (!$match) {
            echo json_encode(['error' => 'That doesn\'t look like a WAD. Can you check?']);
            die();
        }
    }

    function validate_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $filename = 'b' . str_replace(".", "", $ip) . 'b';
        $ip_check_file = IPS_FOLDER . $filename;
        if (file_exists($ip_check_file) && (time() - filemtime($ip_check_file)) < 120) {
            $this->lg("IP " . $ip . " is submitting too fast");
            echo json_encode(['error' => 'You uploaded just a moment ago - hold on a minute before you submit again']);
            die();
        }
        file_put_contents($ip_check_file, ":)");
    }

    function validate_fields($mapname, $authorname) {
        if (empty($mapname)) {
            echo json_encode(['error' => 'A map must have a name!']);
            die();            
        }
        if (empty($authorname)) {
            echo json_encode(['error' => 'A map must have an author name!']);
            die();
        }
    }

    function wait_for_lock() {
        $tries = 0;

        while (file_exists(LOCK_FILE_UPLOAD) && (time() - filemtime(LOCK_FILE_UPLOAD)) < 2) {
            sleep(1);
            if ($tries > 10) {
                echo json_encode(['error' => 'Upload timeout! Probably a site problem, try pressing it again']);
                die();
            }
        }
        file_put_contents(LOCK_FILE_UPLOAD, ":)");
        $this->lg("Lock acquired");
    }
}

//Let's make it look like we're working (discourages spamming this script)
sleep(2);

$filename = isset($_FILES['file']['name']) ? $_FILES['file']['name'] : null;
$filesize = isset($_FILES['file']['size']) ? $_FILES['file']['size'] : 0;
$tmpname = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : null;
$pin = isset($_POST['pin']) ? $_POST['pin'] : null;

if (empty($pin) && (empty($filename) || empty($filesize) || empty($tmpname))) {
    echo json_encode(['error' => 'No file was uploaded!']);
    die();
}

$handler = new Upload_Handler();
$handler->handle_upload($filename, $filesize, $tmpname, $pin);
