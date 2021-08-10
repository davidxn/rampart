<?php
require_once('_constants.php');

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
        //Start from 10 so we have some space for defaults in maps 1-9
        //(and to be honest so that I don't have to do a special case adding a 0 to single digit maps)
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
        $this->catalog[$pin] = $properties;
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
    
}
