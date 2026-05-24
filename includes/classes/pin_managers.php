<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

abstract class Pin_Manager {

    private array $provisional_pins = [];

    public abstract function get_new_pin(): string;

    public function consume_provisional_pin($pin): bool {
        wait_for_lock(LOCK_FILE_PROVISIONAL_PINS);
        $this->load_provisional_pins();
        $found = false;
        $correctCasePin = '';
        foreach ($this->provisional_pins as $provisional_pin) {
            if (strtoupper($provisional_pin) == strtoupper($pin)) {
                $found = true;
                $correctCasePin = $provisional_pin;
                break;
            }
        }
        if (!$found) {
            release_lock(LOCK_FILE_PROVISIONAL_PINS);
            return false;
        }
        //Need to match case here!
        $this->provisional_pins = array_diff($this->provisional_pins, [$correctCasePin]);
        $this->save_provisional_pins();
        release_lock(LOCK_FILE_PROVISIONAL_PINS);
        return true;
    }

    public function get_new_provisional_pin($email): string {
        $new_pin = $this->get_new_pin();
        wait_for_lock(LOCK_FILE_PROVISIONAL_PINS);
        $this->load_provisional_pins();
        $this->provisional_pins[] = $new_pin;
        Logger::lg("{$email} requested a new provisional PIN, generated {$new_pin}");
        $this->save_provisional_pins();
        release_lock(LOCK_FILE_PROVISIONAL_PINS);
        return $new_pin;
    }

    private function load_provisional_pins(): void {
        $string_to_decode = "[]";
        if (file_exists(PROVISIONAL_PIN_FILE)) {
            $string_to_decode = file_get_contents(PROVISIONAL_PIN_FILE);
        }
        $decoded_json = json_decode($string_to_decode, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            die("Provisional PIN file could not be JSON-decoded!");
        }
        if (!$decoded_json) {
            $this->provisional_pins = [];
            return;
        }
        $this->provisional_pins = $decoded_json;
    }

    private function save_provisional_pins(): void {
        file_put_contents(PROVISIONAL_PIN_FILE, json_encode($this->provisional_pins, JSON_PRETTY_PRINT));
    }
}

class Pin_Manager_Preset {

    public function get_new_pin(): string
    {
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
        return $pin;
    }
}

class Pin_Manager_Random extends Pin_Manager {

    private static $source_chars = "acdefghjkmnpqrtvwxy346789";

    public function get_new_pin(): string
    {
        $pin = "";        
        for ($i = 0; $i < 6; $i++) {
            $pin .= substr(self::$source_chars, rand(0, strlen(self::$source_chars)-1), 1);
        }
        return $pin;
    }
}
