<?php

require_once("_constants.php");
require_once("_functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

class Lump_Guardian {
    
    public $global_lump_list = [];
    public $global_texture_list = [];
    public $global_sound_definition_list = [];
    
    public function __construct() {
        $this->add_doom2_lumps();
        $this->add_doom2_sound_definitions();
        $this->add_doom2_textures();
    }
    
    public function add_lump($lump, $owning_map) {
        return $this->add_lump_to_global_list($lump['name'], md5($lump['data']), $owning_map);
    }
    
    public function add_sndinfo_definition($requested_definition, $requested_lump_name, $map_number) {
        $requested_definition = strtolower($requested_definition);
        $requested_lump_name = strtolower($requested_lump_name);
        if (isset($this->global_sound_definition_list[$requested_definition])) {
            if ($this->global_sound_definition_list[$requested_definition] != $requested_lump_name) {
                Logger::pg("❌ SNDINFO tries to define the sound " . $requested_definition . " as " . $requested_lump_name . ", but it's already defined as " . $this->global_sound_definition_list[$requested_definition], $map_number, true);
                return false;
            }
            Logger::pg("⚠️ SNDINFO defines the sound " . $requested_definition . ", which is already defined but it matches the existing definition " . $this->global_sound_definition_list[$requested_definition], $map_number);
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
            $definition = preg_replace("/\s/", "", $definition);
            Logger::pg("Found texture definition for " . $texture_name, $map_number);
            if (isset($this->global_texture_list[$texture_name])) {
                $existing_definition = $this->global_texture_list[$texture_name];
                if ($definition != $existing_definition['definition']) {
                    Logger::pg("❌ TEXTURES attempts to redefine " . $type . " " . $texture_name . " as " . $definition . ", already defined in " . $existing_definition['map'] . " as " . $existing_definition['definition'] . ". Skipping this definition", $map_number, true);
                    $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                    $success = false;
                    continue;
                }
                Logger::pg("⚠️ TEXTURES redefines " . $type . " " . $texture_name . " as " . $definition . ", already defined in " . $existing_definition['map'] . " with an identical definition. Skipping this definition", $map_number);
                $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                continue;
            }
            $this->global_texture_list[$texture_name] = ['map' => $map_number, 'definition' => $definition];
        }
        return ['success' => $success, 'cleaned_data' => $final_texture_data];
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
            Logger::pg("❌ Lump " . $lumpname . " would overwrite existing lump of that name from the project's base IWAD. Please rename it", $owning_map, true);
            return false;
        }
        
        //If the hash matches, notify but don't count as error
        $existing_hash = $this->global_lump_list[$lumpname]['hash'];
        if ($existing_hash == $data_hash) {
            Logger::pg("⚠️ Lump " . $lumpname . " matches same name from map number " . $this->global_lump_list[$lumpname]['owner'] . ". The data is identical", $owning_map);
            return true;
        }
        
        Logger::pg("❌ Lump " . $lumpname . " would overwrite existing lump of that name from map number " . $this->global_lump_list[$lumpname]['owner'] . ". Please rename it to make sure both maps work correctly", $owning_map, true);
        return false;
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_lumps() {
        $lumpnames = explode("\n", file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.lumps"));
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