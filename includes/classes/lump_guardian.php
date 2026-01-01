<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Lump_Guardian {

    public static $reserved_doomed_ranges = [
        [1,127,"Reserved"],
        [888,888,"MBFHelperDog"],
        [1200 ,1209,"Heretic sound sequences"],
        [1400 ,1411,"Hexen sound sequences"],
        [1500 ,1505,"Geometry objects"],
        [2001 ,2049,"Doom objects"],
        [3001 ,3006,"Doom monsters"],
        [4001 ,4004,"Additional playerstarts"],
        [4500 ,4503,"Mine Lamps"],
        [5001 ,5010,"Reserved"],
        [5050 ,5050,"Stalagmite"],
        [5061 ,5065,"Bridge objects"],
        [7000 ,7000,"Grass"],
        [7100 ,7120,"Flora"],
        [9024 ,9048,"Misc script objects"],
        [9050 ,9083,"Stealth monsters that nobody likes"],
        [9100 ,9111,"Scripted marines"],
        [9200 ,9200,"Decal"],
        [9300 ,9303,"Polyobjects"],
        [9500 ,9511,"Ramps"],
        [9600 ,9632,"Wolf3D objects"],
        [9702 ,9724,"Trees"],
        [9800 ,9830,"Lights"],
        [9901 ,9930,"Hub objects"],
        [9980 ,9999,"Event objects"],
        [14001 ,14067,"Sound objects"],
        [14101 ,14165,"Music changers"],
        [32000 ,32000,"Doom Builder Camera"]
    ];
    
    public $global_lump_list = [];
    public $global_texture_list = [];
    public $global_sound_definition_list = [];
    public $global_sound_sequence_list = [];
    public $global_ambient_list = [];
    public $ignore_special_lump_list = ['rampshot', 'rsky1'];
    
    public function __construct() {
        $this->add_doom2_lumps();
        $this->add_doom2_sound_definitions();
        $this->add_doom2_textures();
        $this->add_doom2_sound_sequences();
    }
    
    public function add_lump($lump, $owning_map) {
        return $this->add_lump_to_global_list($lump['name'], md5($lump['data']), $owning_map);
    }
    
    public function in_special_lump_list($lump) {
        return in_array(strtolower($lump['name']), $this->ignore_special_lump_list);
    }
    
    public function doomed_num_in_reserved_range($num) {
        foreach(self::$reserved_doomed_ranges as $range) {
            $start = $range[0];
            $end = $range[1];
            $name = $range[2];
            if ($start <= $num && $end >= $num) {
                return $name;
            }
        }
        return "";
    }
    
    public function add_ambients($requested_ambients, $map_number) {
        foreach ($requested_ambients as $index => $definition) {
            if (isset($this->global_ambient_list[$index])) {
                Logger::pg("❌ SNDINFO tries to define ambient index " . $index . " as " . $definition . ", but it's already reserved by map " . $this->global_ambient_list[$index]['map'], $map_number, true);
                return false;
            }
        }
        foreach ($requested_ambients as $index => $definition) {
            $this->global_ambient_list[$index] = ['definition' => $definition, 'map' => $map_number];
        }
        return true;
    }
    
    public function add_sndinfo_definition($requested_definition, $requested_lump_name, $map_number) {
        $requested_definition = strtolower($requested_definition);
        $requested_lump_name = strtolower($requested_lump_name);
        if (isset($this->global_sound_definition_list[$requested_definition])) {
            if ($this->global_sound_definition_list[$requested_definition] != $requested_lump_name) {
                Logger::pg(get_error_link('ERR_SOUND_SNDINFO_REDEFINITION', [$requested_definition, $requested_lump_name, $this->global_sound_definition_list[$requested_definition]]), $map_number, true);
                return false;
            }
            Logger::pg(get_error_link('WARN_SOUND_SNDINFO_REDEFINITION', [$requested_definition, $this->global_sound_definition_list[$requested_definition]]), $map_number);
        }
        $this->global_sound_definition_list[$requested_definition] = $requested_lump_name;
        return true;
    }
    
    public function validate_textures($texture_data, $map_number) {
        $final_texture_data = $texture_data;
        $success = true;
        $matches = [];
        preg_match_all('/(walltexture|texture|flat|sprite)\s*"?([^\s"]*)?"?,[\s\S]*?{([\s\S]*?)}/im', $texture_data, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $fullmatch = $matches[0][$i];
            $type = $matches[1][$i];
            $texture_name = strtolower($matches[2][$i]);
            $definition = strtolower($matches[3][$i]);
            $definition = strtolower(preg_replace("/\s/", "", $definition));
            Logger::pg("Found texture definition for " . $texture_name, $map_number);
            if (in_array($texture_name, ['aashitty', 'aastinky', 's3dummy'])) {
                Logger::pg(get_error_link('ERR_TEX_DEFINITION_NOT_NEEDED', [$texture_name]), $map_number);
                $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                continue;
            }
            if (isset($this->global_texture_list[$texture_name])) {
                $existing_definition = $this->global_texture_list[$texture_name];
                if ($definition != $existing_definition['definition']) {
                    Logger::pg(get_error_link('ERR_TEX_REDEFINITION_OTHER', [$type, $texture_name, $definition, $existing_definition['map'], $existing_definition['definition']]), $map_number, true);
                    $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                    $success = false;
                    continue;
                }
                Logger::pg(get_error_link('WARN_TEX_REDEFINITION', [$texture_name, $definition, $existing_definition['map']]), $map_number);
                $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                continue;
            }
            $this->global_texture_list[$texture_name] = ['map' => $map_number, 'definition' => $definition];
        }
        return ['success' => $success, 'cleaned_data' => $final_texture_data];
    }

    public function add_sound_sequences($sndseq, $map_number) {
        $final_sndseq_data = $sndseq;
        $success = true;
        $matches = [];
        $matches2 = [];
        preg_match_all('/:([A-Za-z0-9]+)\s+((?:door|platform|slot)\s*?[0-9]+)?[\s\S]*?end/im', $sndseq, $matches);
        preg_match_all('/\[([A-Za-z0-9]+)\s+((?:door|platform|slot)\s*?[0-9]+)?[\s\S]*?\]/im', $sndseq, $matches2);
        $matches[0] = array_merge($matches[0], $matches2[0]);
        $matches[1] = array_merge($matches[1], $matches2[1]);
        $matches[2] = array_merge($matches[2], $matches2[2]);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $fullmatch = $matches[0][$i];
            $seq_name = strtolower($matches[1][$i]);
            $seq_number = strtolower(preg_replace("/\s/", "", $matches[2][$i]));
            $definition_key = strtolower(preg_replace("/\s/", "", $fullmatch));
            Logger::pg("Found SNDSEQ sequence definition for " . $seq_name, $map_number);
            //Check against our list of sound sequence names
            if (isset($this->global_sound_sequence_list['sequences'][$seq_name])) {
                $existing_definition = $this->global_sound_sequence_list['sequences'][$seq_name];
                if ($existing_definition != $definition_key) {
                    Logger::pg("❌ SNDSEQ attempts to redefine " . $seq_name . " as " . $definition_key . ", already defined in " . $existing_definition['map'] . " as " . $existing_definition['definition'] . ". Skipping this definition", $map_number, true);
                    $final_sndseq_data = str_replace($fullmatch, "", $final_sndseq_data);
                    $success = false;
                    continue;
                }
                Logger::pg("⚠️ SNDSEQ redefines " . $seq_name . " as " . $definition_key . ", already defined in " . $existing_definition['map'] . " with an identical definition. Skipping this definition", $map_number);
                $final_sndseq_data = str_replace($fullmatch, "", $final_sndseq_data);
                continue;
            }
            //If we have no sequence number, then just store and continue now
            if (empty($seq_number)) {
                Logger::pg("Reserving sound sequence " . $seq_name);
                $this->global_sound_sequence_list['sequences'][$seq_name] = ['map' => $map_number, 'definition' => $definition_key];
                continue;
            }

            //We have a sequence number - check it isn't 0
            if (in_array($seq_number, ['door0', 'platform0', 'slot0'])) {
                Logger::pg("❌ SNDSEQ can't use number " . $seq_number . "! Assign it an unused number above 0", $map_number, true);
                $final_sndseq_data = str_replace($fullmatch, "", $final_sndseq_data);
                $success = false;
                continue;
            }

            //Now check that it won't override an existing number either
            if (isset($this->global_sound_sequence_list['numbers'][$seq_number])) {
                $existing_definition = $this->global_sound_sequence_list['numbers'][$seq_number];
                if ($existing_definition != $definition_key) {
                    Logger::pg("❌ SNDSEQ attempts to redefine " . $seq_number . " as " . $definition_key . ", already defined in " . $existing_definition['map'] . " as " . $existing_definition['definition'] . ". Skipping this definition", $map_number, true);
                    $final_sndseq_data = str_replace($fullmatch, "", $final_sndseq_data);
                    $success = false;
                    continue;
                }
                Logger::pg("⚠️ SNDSEQ redefines " . $seq_number . " as " . $definition_key . ", already defined in " . $existing_definition['map'] . " with an identical definition. Skipping this definition", $map_number);
                $final_sndseq_data = str_replace($fullmatch, "", $final_sndseq_data);
                continue;
            }

            //We're okay - store sequence name and number
            $this->global_sound_sequence_list['sequences'][$seq_name] = ['map' => $map_number, 'definition' => $definition_key];
            $this->global_sound_sequence_list['numbers'][$seq_number] = ['map' => $map_number, 'definition' => $definition_key];
        }
        return ['success' => $success, 'cleaned_data' => $final_sndseq_data];
    }
    
    private function add_lump_to_global_list($lumpname, $data_hash, $owning_map) {
        $lumpname = strtolower($lumpname);
        //If we have no lump by this name recorded, add it
        if (!isset($this->global_lump_list[$lumpname])) {
            $this->global_lump_list[$lumpname] = [];
            $this->global_lump_list[$lumpname]['owner'] = $owning_map;
            $this->global_lump_list[$lumpname]['hash'] = $data_hash;
            return true;
        }
        //We already had a lump with this name. Look to see if it belongs to our base resources or a map, and if it's identical to the existing one
        //IWAD overwrites are never allowed
        if ($this->global_lump_list[$lumpname]['owner'] == "IWAD") {
            Logger::pg(get_error_link('ERR_LUMP_DUPLICATE_BASE', [$lumpname]), $owning_map, true);
            return false;
        }
        
        //If the hash matches, notify but don't count as error
        $existing_hash = $this->global_lump_list[$lumpname]['hash'];
        if ($existing_hash == $data_hash) {
            Logger::pg(get_error_link('WARN_LUMP_DUPLICATE_OTHER', [$lumpname, $this->global_lump_list[$lumpname]['owner']]), $owning_map);
            return true;
        }
        Logger::pg(get_error_link('ERR_LUMP_DUPLICATE_OTHER', [$lumpname, $this->global_lump_list[$lumpname]['owner']]), $owning_map, true);
        return false;
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_lumps() {
        $lump_file_contents = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.lumps");
        $lumpnames = explode("\n", $lump_file_contents);
        Logger::pg("Read " . count($lumpnames) . " protected lumps");
        foreach ($lumpnames as $lumpname) {
            $lumpname = strtolower(trim($lumpname));
            if ($lumpname != "") {
                $this->add_lump_to_global_list($lumpname, "", "IWAD");
            }
        }
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_textures() {
        $texture_data = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.textures");
        Logger::pg("Adding base textures to global texture list");
        $this->validate_textures($texture_data, "IWAD");
    }

    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_sound_sequences() {
        $sndseq = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.sndseq");
        Logger::pg("Adding base sound sequences to global sound sequence list");
        $this->validate_textures($sndseq, "IWAD");
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_sound_definitions() {
        $lines = explode("\n", file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.sndinfo"));
        Logger::pg("Read " . count($lines) . " protected SNDINFO entries");
        foreach ($lines as $line) {
            $elements = $tokens = preg_split('/\s+/', trim($line));
            $sounddef = strtolower(trim($elements[0]));
            $lumpname = strtolower(trim($elements[1]));
            if ($lumpname != "") {
                $this->global_sound_definition_list[$sounddef] = $lumpname;
            }
        }
    }
    
}
