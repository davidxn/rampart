<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/logger.php');

class Pin_Manager_Preset {

    public static function get_new_pin() {
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

class Pin_Manager_Random {

    private static $source_chars = "ABCDEFGHJKLMNOPRSTUVWXYZ1346789";

    public static function get_new_pin() {
        $pin = "";        
        for ($i = 0; $i < 6; $i++) {
            $pin .= substr(self::$source_chars, rand(0, strlen(self::$source_chars)-1), 1);
        }
        return $pin;
    }
}
