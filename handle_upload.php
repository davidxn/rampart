<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "includes/classes/pin_managers.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "data/wad_validator.php");

class Upload_Handler {

    public Wad_Validator $validator;

    function handle_upload($filename, $filesize, $tmp_name, $pin) {

        Logger::lg("Starting an upload attempt");
        
        $this->validator = new Wad_Validator($filename);

        $mapname = $this->clean_text($_POST['mapname']);
        $authorname = $this->clean_text($_POST['authorname']);
        $musiccredit = $this->clean_text($_POST['musiccredit']);
        $jumpcrouch = $this->clean_text($_POST['jumpcrouch']);
        $category = $this->clean_text($_POST['category']);
        $length = $this->clean_text($_POST['length']);
        $difficulty = $this->clean_text($_POST['difficulty']);
        $monsters = $this->clean_number($_POST['monsters']);
        $wip = isset($_POST['wip']) ? $this->clean_text($_POST['wip']) : 0;
        $pin = strtoupper($this->clean_text($pin));

        $this->validate_fields($mapname, $authorname, $musiccredit);
        $this->validate_ip();
        if ($tmp_name) {
            $this->validate_wad_header($tmp_name);
        }

        //Mutex
        if (!wait_for_lock(LOCK_FILE_UPLOAD)) {
            echo json_encode(['error' => 'Upload timeout! Probably a site problem, try pressing it again']);
            die();
        }

        $catalog_handler = new Catalog_Handler();

        $existing_map = null;
        if ($pin) {
            $existing_map = $this->validate_pin($pin, $catalog_handler);
            $ramp_id = $existing_map->rampId;
            $map_lumpname = $existing_map->lump;
            $map_number = $existing_map->mapnum;
        }
        else {
            $pin_manager = get_setting("PIN_MANAGER_CLASS");
            $pin = $pin_manager::get_new_pin();
            if (empty($pin)) {
                echo json_encode(['error' => 'Error creating a PIN! Ask the project owner about this.']);
                die();
            }
            $ramp_id = $catalog_handler->get_next_available_slot();
            $map_lumpname = ($ramp_id < 10 ? ('MAP0' . $ramp_id) : ('MAP' . $ramp_id));
            $map_number = $ramp_id;
            Logger::lg("Assigning PIN: " . $pin);
            Logger::lg("Assigning RAMP ID and map number: " . $ramp_id);
        }
        
        $location = null;
        //If a file has been uploaded, move it to our store.
        if ($tmp_name) {
            @mkdir(UPLOADS_FOLDER);
            $location = UPLOADS_FOLDER . get_source_wad_file_name($ramp_id);
            Logger::lg("Moving file " . $tmp_name . " to " . $location);
            if (file_exists($location)) {
                unlink($location);
            }
            $result = move_uploaded_file($tmp_name, $location);

            if(!$result){
                echo json_encode(['error' => 'Upload error!']);
                die();
            }
        }

        //Now update the catalog
        $catalog_handler->update_map_properties(
            $ramp_id,
            [
                'pin' => $pin,
                'lump' => $map_lumpname,
                'mapnum' => $map_number,
                'disabled' => 0, // Re-enable a map on reupload

                'name' => $mapname,
                'author' => $authorname,
                'musicCredit' => $musiccredit,
                'jumpCrouch' => $jumpcrouch,
                'wip' => $wip,
                'category' => $category,
                'length' => $length,
                'difficulty' => $difficulty,
                'monsterCount' => $monsters,
            ]
        );
        Logger::lg("Wrote map ID " . $ramp_id . ": " . $pin . " entry to catalog");

        //Unmutex
        release_lock(LOCK_FILE_UPLOAD);
        Logger::record_upload(time(), $ramp_id, $location ? filesize($location) : 0);
        Logger::lg("Lock released");
        
        //Remove any existing logs for this map
        Logger::clear_log_for_map($ramp_id);
        
        if (!$existing_map && get_setting('NOTIFY_ON_MAPS') != 'never' && !empty(get_setting('NOTIFY_EMAIL'))) {
            mail(get_setting('NOTIFY_EMAIL'), "RAMPART: New map added", "A new map '" . $mapname . "' by " . $authorname . " has been added to the project as " . $map_lumpname . ".");
        } else if ($existing_map && get_setting('NOTIFY_ON_MAPS') == 'all') {
            mail(get_setting('NOTIFY_EMAIL'), "RAMPART: Map updated", "Map '" . $map_lumpname . " " . $mapname . "' by " . $authorname . " has just been updated.");
        }

        $success_message = "Success! Your WAD has been added to the project with map ID " . $ramp_id .
            " It will be included in the project as " . $map_lumpname . ". " .
            "Your PIN is: <div style=\"font-size: 64pt; font-weight: bold; text-align: center\">" . $pin . "</div>Use this if you ever need to update your level.";
        if ($existing_map) {
            $success_message = "Success! Map ID " . $ramp_id . " in lump " . $map_lumpname . "</b> has been updated.";
        }

        echo json_encode(["name" => $filename, "size" => $filesize, "pin" => $pin, "map_number" => $map_number, "success" => $success_message]);
    }

    function clean_text($string, $length = 0) {
       $string = trim($string);
       $string = preg_replace('/[^A-Za-z0-9\-\'!:\)\(\. ]/', '', $string); // Removes special chars.
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }
    
    function clean_number($string, $length = 0) {
       $string = trim($string);
       $string = preg_replace('/[^0-9]/', '', $string);
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }

    function validate_pin(string $pin, Catalog_Handler $catalog_handler) {
        $map = $catalog_handler->get_map_by_pin($pin);
        if (!$map) {
            sleep(10);
            echo json_encode(['error' => 'Provided PIN did not match any map']);
            die();
        }
        $locked = $catalog_handler->is_map_locked($map->rampId);
        if ($locked) {
            echo json_encode(['error' => 'This map is locked for edits! Contact the project owner if you need to update it.']);
            die();
        }

        return $map;
    }

    function validate_wad_header($filename): void {
        $file = fopen($filename,"r");
        $bytes = fread($file, 4);
        fclose($file);
        $match = ($bytes == "PWAD");
        Logger::lg("First four bytes " . ($match ? "match" : "do not match") . " PWAD header");
        if (!$match) {
            echo json_encode(['error' => "That doesn't look like a WAD. Can you check?"]);
            die();
        }
        $this->validator->validate();
    }

    function validate_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        Logger::lg("Recording upload from IP " . $ip);        

        @mkdir(IPS_FOLDER);
        
        $ban_list_file = DATA_FOLDER . "ipbans";
        if (file_exists($ban_list_file)) {
            $ip_ban_list = file_get_contents($ban_list_file);
            $ip_prefixes = explode("\n", $ip_ban_list);
            foreach ($ip_prefixes as $ip_prefix) {
                if (str_starts_with($ip, $ip_prefix)) {
                    Logger::lg("Blocked upload attempt from IP " . $ip . " due to match with prefix " . $ip_prefix);
                    $this->validator->handle_validation_failure();
                }
            }
        }

        $filename = 'b' . $ip . 'b';
        $ip_check_file = IPS_FOLDER . $filename;
        
        if (file_exists($ip_check_file) && (time() - filemtime($ip_check_file)) < get_setting("UPLOAD_ATTEMPT_GAP")) {
            Logger::lg("IP " . $ip . " is submitting too fast");
            echo json_encode(['error' => 'You uploaded just a moment ago - hold on a minute before you submit again']);
            die();
        }
        file_put_contents($ip_check_file, ":)");
    }

    function validate_fields($mapname, $authorname, $musiccredit) {
        $this->detect_bad_words($mapname);
        $this->detect_bad_words($authorname);
        $this->detect_bad_words($musiccredit);
        if (strlen($mapname) > 50) {
            echo json_encode(['error' => 'Map name can only be up to 50 characters']);
            die();
        }
        if (strlen($authorname) > 50) {
            echo json_encode(['error' => 'Author can only be up to 50 characters']);
            die();
        }
        if (strlen($musiccredit) > 50) {
            echo json_encode(['error' => 'Music credit can only be up to 50 characters']);
            die();
        }
        if (empty($mapname)) {
            echo json_encode(['error' => 'A map must have a name!']);
            die();            
        }
        if (empty($authorname)) {
            echo json_encode(['error' => 'A map must have an author name!']);
            die();
        }
    }
    
    function detect_bad_words($phrase) {
        $ban_list_file = DATA_FOLDER . "badwords";
        if (file_exists($ban_list_file)) {
            $ban_list = file_get_contents($ban_list_file);
            $ban_array = explode("\n", $ban_list);
            foreach ($ban_array as $ban) {
                if (str_contains(strtolower($phrase), strtolower($ban))) {
                    Logger::lg("Blocked upload attempt due to match with banned word");
                    $validator = new Wad_Validator('');
                    $validator->handle_validation_failure();
                }
            }
        }
    }
}

//Let's make it look like we're working (discourages spamming this script)
sleep(2);

$filename = $_FILES['file']['name'] ?? '';
$filesize = $_FILES['file']['size'] ?? 0;
$tmp_name = $_FILES['file']['tmp_name'] ?? null;
$pin = $_POST['pin'] ?? null;

if (empty($pin) && (empty($filename) || empty($filesize) || empty($tmp_name))) {
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
$handler->handle_upload($filename, $filesize, $tmp_name, $pin);
