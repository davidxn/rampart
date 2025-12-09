<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Catalog_Handler {
    
    private $catalog = [];
    
    public function __construct() {
        $string_to_decode = "[]";
        if (file_exists(CATALOG_FILE)) {            
            $string_to_decode = file_get_contents(CATALOG_FILE);
        }
        $this->catalog = json_decode($string_to_decode, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            die("Catalogue file could not be JSON-decoded!");
        }
        if (!$this->catalog) {
            $this->catalog = [];
        }
    }
    
    public function get_next_available_slot() {
        $occupied_slots = [];
        foreach($this->catalog as $mapdata) {
            $occupied_slots[$mapdata['map_number']] = true;
        }
        $examined_slot = FIRST_USER_MAP_NUMBER;
        while (true) {
            if (!isset($occupied_slots[$examined_slot])) {
                break;
            }
            $examined_slot++;
        }
        return $examined_slot;
    }
    
    public function update_map_properties($pin, $properties) {
        if (!isset($this->catalog[$pin])) {
            $this->catalog[$pin] = [];
        }
        foreach ($properties as $property => $value) {
            $this->catalog[$pin][$property] = $value;
        }
        file_put_contents(CATALOG_FILE, json_encode($this->catalog));
    }
    
    public function update_map_property($pin, $property, $value) {
        $this->catalog[$pin][$property] = $value;
        file_put_contents(CATALOG_FILE, json_encode($this->catalog));
    }
    
    public function get_map($pin) {
        if ($this->catalog[$pin]) {
            return $this->catalog[$pin];
        }
        return false;
    }
    
    public function get_map_by_number($map_number) {
        foreach ($this->catalog as $pin => $data) {
            if ($data['map_number'] == $map_number) {
                return $data;
            }
        }
    }
    
    public function is_map_locked($pin) {
        $map = $this->get_map($pin);
        if (!$map) {
            return false;
        }
        return isset($map['locked']) ? $map['locked'] : 0;
    }
    
    public function get_catalog() {
        return $this->catalog;
    }
    
    public function lock_map($pin) {
        if ($this->catalog[$pin]) {
            $this->update_map_property($pin, 'locked', 1);
        }
    }
    
    public function unlock_map($pin) {
        if ($this->catalog[$pin]) {
            $this->update_map_property($pin, 'locked', 0);
        }
    }
    
    public function delete_map($pin) {
        unset($this->catalog[$pin]);
        file_put_contents(CATALOG_FILE, json_encode($this->catalog));    
    }

    public function disable_map($pin) {
        if ($this->catalog[$pin]) {
            $this->update_map_property($pin, 'disabled', 1);
        }
    }    
    
    public function change_pin($pin, $new_pin) {
        $new_pin = strtoupper($new_pin);
        if (isset($this->catalog[$new_pin])) {
            return false;
        }
        $this->catalog[$new_pin] = $this->catalog[$pin];
        $this->delete_map($pin);
        return true;
    }
    
    public function move_map($pin, $map_number) {
        if (!$this->catalog[$pin]) {
            Logger::lg("Was asked to move map with pin " . $pin . " which doesn't exist");
            return false;
        }
        if (!$this->wait_for_lock()) {
            return false;
        }
        $original_levelnum = $this->catalog[$pin]['map_number'];
        
        $source_location = UPLOADS_FOLDER . DIRECTORY_SEPARATOR . get_source_wad_file_name($original_levelnum);
        $location = UPLOADS_FOLDER . DIRECTORY_SEPARATOR . get_source_wad_file_name($map_number);
        
        if (file_exists($location)) {
            Logger::lg("Removed previous " . $location);
            unlink($location);
        }
        Logger::lg("Moving map " . $source_location . " to " . $location);
        $result = rename($source_location, $location);

        if(!$result){
            return false;
        }
        
        $map_lumpname = ($map_number < 10 ? ('MAP0' . $map_number) : ('MAP' . $map_number));

        //Now update the catalog... remove any existing map at this map number
        foreach ($this->catalog as $oldpin => $data) {
            if ($data['map_number'] == $map_number) {
                unset($this->catalog[$oldpin]);
            }
        }
        
        //And update the map at our old pin to our new number.
        $this->update_map_properties(
            $pin,
            [
                'map_number' => $map_number,
                'lumpname' => $map_lumpname
            ]
        );
        Logger::lg("Wrote new map " . $map_number . ": " . $pin . " entry to catalog");

        //Unmutex
        unlink(LOCK_FILE_UPLOAD);
        return true;
    }
    
    function wait_for_lock() {
        $tries = 0;

        while (file_exists(LOCK_FILE_UPLOAD) && (time() - filemtime(LOCK_FILE_UPLOAD)) < 60) {
            sleep(1);
            if ($tries > 10) {
                return false;
            }
        }
        file_put_contents(LOCK_FILE_UPLOAD, ":)");
        Logger::lg("Lock acquired");
        return true;
    }
    
}
