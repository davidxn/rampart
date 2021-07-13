<?
require_once('_constants.php');

class Pin_Manager {

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