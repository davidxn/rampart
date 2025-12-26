<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Catalog_Handler {


    /** @var RampMap[] $catalog */
    private array $catalog = [];
    private array $pinsToRampIds = [];
    
    public function __construct() {
        $string_to_decode = "[]";
        if (file_exists(CATALOG_FILE)) {            
            $string_to_decode = file_get_contents(CATALOG_FILE);
        }
        $decoded_json = json_decode($string_to_decode, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            die("Catalogue file could not be JSON-decoded!");
        }
        if (!$decoded_json) {
            $this->catalog = [];
            return;
        }
        foreach ($decoded_json as $pin => $mapdata) {
            $rampMap = new RampMap($pin, $mapdata);
            $this->catalog[$rampMap->rampId] = $rampMap;
            $this->pinsToRampIds[$pin] = $rampMap->rampId;
        }
    }

    private function save_catalog(): void {
        file_put_contents(CATALOG_FILE, json_encode($this->catalog));
    }
    
    public function get_next_available_slot(): int
    {

        $max_slot = max(array_keys($this->catalog));
        return $max_slot + 1;
    }
    
    public function update_map_properties($rampId, $properties): void {
        if (!isset($this->catalog[$rampId])) {
            $this->catalog[$rampId] = new RampMap($rampId, $properties);
        }
        foreach ($properties as $property => $value) {
            $this->catalog[$rampId]->$property = $value;
        }
        $this->save_catalog();
    }
    
    public function update_map_property($rampId, $property, $value): void {
        $this->catalog[$rampId]->$property = $value;
        Logger::lg("Edited $rampId property: $property to $value");
        $this->save_catalog();
    }
    
    public function get_map_by_pin($pin): ?RampMap {
        if ($this->pinsToRampIds[$pin]) {
            return $this->catalog[$this->pinsToRampIds[$pin]];
        }
        return null;
    }
    
    public function get_map_by_ramp_id($rampId): RampMap {
        return $this->catalog[$rampId];
    }
    
    public function is_map_locked($rampId): bool {
        $map = $this->get_map_by_ramp_id($rampId);
        if (!$map) {
            return false;
        }
        return $map['locked'] ?? 0;
    }
    
    public function get_catalog(): array
    {
        return $this->catalog;
    }
    
    public function lock_map($rampId): void
    {
        if ($this->catalog[$rampId]) {
            $this->update_map_property($rampId, 'locked', 1);
        }
    }
    
    public function unlock_map($rampId): void
    {
        if ($this->catalog[$rampId]) {
            $this->update_map_property($rampId, 'locked', 0);
        }
    }
    
    public function delete_map($rampId): void {
        unset($this->catalog[$rampId]);
        $this->save_catalog();
    }

    public function disable_map($rampId): void {
        if ($this->catalog[$rampId]) {
            $this->update_map_property($rampId, 'disabled', 1);
        }
    }    
    
    public function change_pin($rampId, $new_pin): bool {
        $new_pin = strtoupper($new_pin);
        //A map PIN must be unique, check the list first
        if (isset($this->pinsToRampIds[$new_pin])) {
            return false;
        }
        $this->catalog[$rampId]->pin = $new_pin;
        $this->save_catalog();
        return true;
    }
}
