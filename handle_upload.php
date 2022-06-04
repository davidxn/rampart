<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/catalog_handler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/pin_managers.php");

class Upload_Handler {

    public $txid = null;
    public $catalog_handler = null;

    function handle_upload($filename, $filesize, $tmpname, $pin) {

        Logger::lg("Starting an upload attempt");

        $mapname = $this->clean_text($_POST['mapname']);
        $authorname = $this->clean_text($_POST['authorname']);
        $musiccredit = $this->clean_text($_POST['musiccredit']);
        $jumpcrouch = $this->clean_text($_POST['jumpcrouch']);
        $wip = 0;
        if (isset($_POST['wip'])) { $wip = $this->clean_text($_POST['wip']); }
        $pin = strtoupper($this->clean_text($pin));

        $this->validate_fields($mapname, $authorname);
        $this->validate_ip();
        if ($tmpname) {
            $this->validate_header($tmpname);
        }

        //Mutex
        $this->wait_for_lock();

        Logger::lg("POST data is: " . print_r($_POST, true));

        $catalog_handler = new Catalog_Handler();
        
        $existing_map = null;
        if ($pin) {
            $existing_map = $this->validate_pin($pin, $catalog_handler);
            $map_number = $existing_map['map_number'];
            $map_lumpname = isset($existing_map['lumpname']) ? $existing_map['lumpname'] : ("MAP" . (substr("0" . $map_number, -2)));
        }
        else {
            $pin_manager = get_setting("PIN_MANAGER_CLASS");
            $pin = $pin_manager::get_new_pin();
            if (empty($pin)) {
                echo json_encode(['error' => 'Error creating a PIN! Ask the project owner about this.']);
                die();
            }
            $map_number = $catalog_handler->get_next_available_slot();
            $map_lumpname = "MAP" . (substr("0" . $map_number, -2));
            Logger::lg("Assigning PIN: " . $pin);
            Logger::lg("Assigning map number: " . $map_number);
        }
        
        //Finalize the file, if we have one
        if ($tmpname) {
            @mkdir(UPLOADS_FOLDER);
            $location = UPLOADS_FOLDER . get_source_wad_file_name($map_number);
            Logger::lg("Moving file " . $tmpname . " to " . $location);
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
        $catalog_handler->update_map_properties(
            $pin,
            [
                'map_name' => $mapname,
                'author' => $authorname,
                'music_credit' => $musiccredit,
                'map_number' => $map_number,
                'lumpname' => $map_lumpname,
                'jumpcrouch' => $jumpcrouch,
                'wip' => $wip
            ]
        );
        Logger::lg("Wrote map " . $map_number . ": " . $pin . " entry to catalog");

        //Unmutex
        unlink(LOCK_FILE_UPLOAD);
        Logger::record_upload(time(), $map_number, filesize($location));
        Logger::lg("Lock released");
        
        if (!$existing_map && get_setting('NOTIFY_ON_MAPS') != 'never' && !empty(get_setting('NOTIFY_EMAIL'))) {
            mail(get_setting('NOTIFY_EMAIL'), "RAMPART: New map added", "A new map '" . $mapname . "' by " . $authorname . " has been added to the project as " . $map_lumpname . ".");
        } else if ($existing_map && get_setting('NOTIFY_ON_MAPS') == 'all') {
            mail(get_setting('NOTIFY_EMAIL'), "RAMPART: Map updated", "Map '" . $map_lumpname . " " . $mapname . "' by " . $authorname . " has just been updated.");
        }

        $success_message = "Success! Your WAD has been added to the project as map MAP" . $map_number . ". Your PIN is <b>" . $pin . "</b> - use this if you ever need to update your level.";
        if ($existing_map) {
            $success_message = "Success! Your WAD in slot " . $map_number . " with PIN <b>" . $pin . "</b> has been updated.";
        }

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

    function get_slot_and_pin($catalog_handler) {

        return ['map_number' => $map_number, 'pin' => $new_pin];
    }
    
    function validate_pin($pin, $catalog_handler) {
        $map = $catalog_handler->get_map($pin);
        if (!$map) {
            echo json_encode(['error' => 'That PIN doesn\'t exist, stop messing with the site please']);
            die();
        }
        $locked = $catalog_handler->is_map_locked($pin);
        if ($locked) {
            echo json_encode(['error' => 'This map is locked for edits! Contact the project owner if you need to update it.']);
            die();
        }

        return $map;
    }

    function validate_header($filename) {
        $file = fopen($filename,"r");
        $bytes = fread($file, 4);
        fclose($file);
        $match = ($bytes == "PWAD");
        Logger::lg("First four bytes " . ($match ? "match" : "do not match") . " PWAD header");
        if (!$match) {
            echo json_encode(['error' => 'That doesn\'t look like a WAD. Can you check?']);
            die();
        }
    }

    function validate_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $filename = 'b' . str_replace(".", "", $ip) . 'b';
        $ip_check_file = IPS_FOLDER . $filename;
        @mkdir(IPS_FOLDER);
        if (file_exists($ip_check_file) && (time() - filemtime($ip_check_file)) < get_setting("UPLOAD_ATTEMPT_GAP")) {
            Logger::lg("IP " . $ip . " is submitting too fast");
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

        while (file_exists(LOCK_FILE_UPLOAD) && (time() - filemtime(LOCK_FILE_UPLOAD)) < 60) {
            sleep(1);
            if ($tries > 10) {
                echo json_encode(['error' => 'Upload timeout! Probably a site problem, try pressing it again']);
                die();
            }
        }
        file_put_contents(LOCK_FILE_UPLOAD, ":)");
        Logger::lg("Lock acquired");
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

if (!$pin && !get_setting("ALLOW_NEW_UPLOADS")) {
    echo json_encode(['error' => 'New uploads are currently disabled! If you want to edit an existing map, please use a PIN']);
    die();
}

if ($pin && !get_setting("ALLOW_EDIT_UPLOADS")) {
    echo json_encode(['error' => 'Edits are currently disabled!']);
    die();
}


$handler = new Upload_Handler();
$handler->handle_upload($filename, $filesize, $tmpname, $pin);
