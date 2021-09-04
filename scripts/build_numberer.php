<?php
require_once('_constants.php');

class Build_Numberer {

    private $current_build_number = 0;
    
    public function __construct() {
        if (file_exists(SNAPSHOT_ID_FILE)) {            
            $this->current_build_number = file_get_contents(SNAPSHOT_ID_FILE);
        } else {
            $this->current_build_number = 0;
        }
    }
    
    public function get_current_build() {
        return $this->current_build_number;
    }
    
    public function set_new_build_number($number) {
        $this->current_build_number = $number;
        file_put_contents(SNAPSHOT_ID_FILE, $this->current_build_number);
    }
    
    public function get_current_build_filename($number) {
        $name = get_setting('PROJECT_FILE_NAME');
        $extension = strtolower(substr($name, -4));
        if ($extension == ".pk3" || $extension == ".wad") {
            $name = substr($name, 0, -4);
        }
        $name .= "-SNAPSHOT-" . $this->get_current_build();
        $name .= get_setting('PROJECT_FORMAT') == 'PK3' ? ".pk3" : ".wad";
        
        return $name;
    }
}
