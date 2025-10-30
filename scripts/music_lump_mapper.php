<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Music_Lump_Mapper {
    
    private $map = [];
    
    public function __construct() {
        $map_type = get_setting("MUSIC_LUMP_MAP");
        if (in_array($map_type, ['udoom', 'doom2'])) {
            $map_string = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . $map_type . '.music');
            $lines = explode("\n", $map_string);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) { continue; }
                $elements = explode(",", $line);
                $this->map[$elements[0]] = $elements[1];
            }
        }
    }
    
    public function get_name_for_music_lump($maplumpname) {
        if (isset($this->map[$maplumpname])) {
            return $this->map[$maplumpname];
        }
        return substr("M_" . $maplumpname, 0, 8);
    }    
}
