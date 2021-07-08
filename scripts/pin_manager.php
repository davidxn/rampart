<?
require_once('_constants.php');

class Pin_Manager {
    
    $catalog = [];
    
    public function __construct($catalog = null) {
        $this->catalog = $catalog ?: @json_decode(file_get_contents(CATALOG_FILE), true) ?: [];
    }

    public function get_new_pin() {
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

        //Now assign a map number by looking at the lowest unoccupied slot.
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
        Logger::lg("Assigning map number: " . $examined_slot);       
        return ['map_number' => $examined_slot, 'pin' => $pin];
    }
    
    public function get_map_by_pin($pin) {
        $map = isset($this->catalog[$pin]) ? $this->catalog[$pin] : null;
        return $map;
    }
}