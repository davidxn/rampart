<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');

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
    
    public function change_pin($pin, $new_pin) {
        $new_pin = strtoupper($new_pin);
        if (isset($this->catalog[$new_pin])) {
            return false;
        }
        $this->catalog[$new_pin] = $this->catalog[$pin];
        $this->delete_map($pin);
        return true;
    }
    
}
