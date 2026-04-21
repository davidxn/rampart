<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class LumpRegistry implements JsonSerializable {

    /**
     * @var ReservedDoomEdNumRange[]
     */
    public array $reservedDoomEdNumRanges;

    /**
     * @var ReservedLump[]
     */
    public array $reservedLumps = [];

    /**
     * @var RejectedLump[]
     */
    public array $rejectedLumps = [];

    /**
     * @var ReservedLump[]
     */
    public array $global_texture_list = [];
    public array $global_sound_definition_list = [];
    public array $global_sound_sequence_list = [];
    public array $global_ambient_list = [];
    /**
     * @var string[]
     */
    public array $ignore_special_lump_list = ['rampshot', 'rsky1'];

    /**
     * @var ReservedIdentifier[]
     */
    private array $doomEdNums;

    /**
     * @var ReservedIdentifier[]
     */
    private array $spawnNums;

    /**
     * @var RejectedIdentifier[]
     */
    private array $rejectedDoomEdNums;

    /**
     * @var RejectedIdentifier[]
     */
    private array $rejectedSpawnNums;

    /**
     * @var int[]
     */
    private array $rampIdsWithRejectedScripts;
    
    public function __construct() {
        $this->doomEdNums = [];
        $this->spawnNums = [];
        $this->rejectedDoomEdNums = [];
        $this->rejectedSpawnNums = [];
        $this->rampIdsWithRejectedScripts = [];

        $this->set_up_reserved_ranges();
        $this->add_doom2_lumps();
        $this->add_doom2_sound_definitions();
        $this->add_doom2_textures();
        $this->add_doom2_sound_sequences();
    }
    
    public function reserveLump($lump, $owning_map_ramp_id): bool
    {
        return $this->add_lump_to_global_list($lump->name, md5($lump->data), $owning_map_ramp_id);
    }
    
    public function nameIsInSpecialLumpList($lump): bool
    {
        return in_array(strtolower($lump->name), $this->ignore_special_lump_list);
    }
    
    public function getRangeForDoomEdNum($num): ?ReservedDoomEdNumRange {
        foreach($this->reservedDoomEdNumRanges as $range) {
            if ($range->containsDoomEdNum($num)) {
                return $range;
            }
        }
        return null;
    }

    private function addDoomEdNum($number, $rampId, $className) {
        $this->doomEdNums[$number] = new ReservedIdentifier($number, $rampId, $className);
    }

    private function addSpawnNum($number, $rampId, $className) {
        $this->spawnNums[$number] = new ReservedIdentifier($number, $rampId, $className);
    }

    private function addRejectedDoomEdNum($number, $owningRampId, $attemptingRampId, $className) {
        $this->rejectedDoomEdNums[$number] = new RejectedIdentifier($number, $owningRampId, $attemptingRampId, $className);
    }

    private function addRejectedSpawnNum($number, $owningRampId, $attemptingRampId, $className) {
        $this->rejectedSpawnNums[$number] = new RejectedIdentifier($number, $owningRampId, $attemptingRampId, $className);
    }

    public function addRejectedScript($attemptingRampId) {
        $this->rampIdsWithRejectedScripts[] = $attemptingRampId;
    }

    public function getDoomEdNum($number) {
        return $this->doomEdNums[$number] ?? null;
    }

    public function getSpawnNum($number) {
        return $this->spawnNums[$number] ?? null;
    }

    
    public function add_ambients($requested_ambients, $map_number) {
        foreach ($requested_ambients as $index => $definition) {
            if (isset($this->global_ambient_list[$index])) {
                Logger::pg(get_error_link('ERR_SOUND_AMBIENT_REDEFINITION', [$index, $definition, $this->global_ambient_list[$index]['map']]), $map_number, true);
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
                $existing_reservation = $this->global_texture_list[$texture_name];
                if ($definition != $existing_reservation->definition) {
                    Logger::pg(get_error_link('ERR_TEX_REDEFINITION_OTHER', [$type, $texture_name, $definition, $existing_reservation->ownerRampId, $existing_reservation->definition]), $map_number, true);
                    $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                    $success = false;
                    continue;
                }
                Logger::pg(get_error_link('WARN_TEX_REDEFINITION', [$texture_name, $definition, $existing_reservation->ownerRampId]), $map_number);
                $final_texture_data = str_replace($fullmatch, "", $final_texture_data);
                continue;
            }
            $this->global_texture_list[$texture_name] = new ReservedLump($texture_name, $map_number, $definition);
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

    public function reserveDoomEdNumber($number, $rampId, $className): bool
    {
        $detected_doomed_num_range = $this->getRangeForDoomEdNum($number);
        if ($detected_doomed_num_range) {
            Logger::pg("❌ DoomedNum conflict: " . $number . " for " . $className . " is in reserved range " . $detected_doomed_num_range->toString() . ", see the Build Info page - rejecting it", $rampId, true);
            $this->addRejectedDoomEdNum($number, 0, $rampId, $className);
            return false;
        }

        $alreadyReservedDoomEdNumber = $this->getDoomEdNum($number);

        if ($alreadyReservedDoomEdNumber) {
            if (strtolower($className) != strtolower($alreadyReservedDoomEdNumber->className)) {
                Logger::pg("❌ DoomedNum conflict: " . $number . " for " . $className . " already refers to " . $alreadyReservedDoomEdNumber->className . " from map " . $alreadyReservedDoomEdNumber->ownerRampId . ", rejecting it", $rampId, true);
                $this->addRejectedDoomEdNum($number, $alreadyReservedDoomEdNumber->ownerRampId, $rampId, $className);
                return false;
            }
            Logger::pg("⚠️ DoomedNum " . $number . " is already defined for " . $className . " in map " . $alreadyReservedDoomEdNumber->ownerRampId . ". Skipping it, but not considering it an error", $rampId);
            return true;
        }
        $this->addDoomEdNum($number, $rampId, $className);
        Logger::pg("Reserved DoomEdNum " . $number . " = " . $className, $rampId);
        return true;
    }

    public function reserveSpawnNumber($number, $rampId, $className): bool
    {
        $alreadyReservedSpawnNumber = $this->getSpawnNum($number);
        if ($alreadyReservedSpawnNumber) {
            if (strtolower($className) != strtolower($alreadyReservedSpawnNumber->number)) {
                Logger::pg("❌ Spawnnum conflict: " . $number . " for " . $className . " already refers to " . $alreadyReservedSpawnNumber->className . " from map " . $alreadyReservedSpawnNumber->ownerRampId . ", rejecting it", $rampId, true);
                $this->addRejectedSpawnNum($number, $alreadyReservedSpawnNumber->ownerRampId, $rampId, $className);
                return false;
            }
            Logger::pg("⚠️ Spawnnum " . $number . " is already defined for " . $className . " in map " . $alreadyReservedSpawnNumber->ownerRampId . ". Skipping it, but not considering it an error", $rampId);
            return true;
        }
        $this->addSpawnNum($number, $rampId, $className);
        Logger::pg("Reserved Spawnnum " . $number . " = " . $className, $rampId);
        return true;
    }

    public function mapHasRejectedScript($rampId): bool
    {
        return (bool) ($this->rampIdsWithRejectedScripts[$rampId] ?? false);
    }

    public function hasDoomEdNumbers(): bool
    {
        return count($this->doomEdNums) > 0;
    }

    public function hasSpawnNumbers(): bool
    {
        return count($this->spawnNums) > 0;
    }

    public function getDoomEdNumbers(): array
    {
        return $this->doomEdNums;
    }

    public function getSpawnNumbers(): array
    {
        return $this->spawnNums;
    }
    
    private function add_lump_to_global_list($lump_name, $data_hash, $attemptingMapRampId): bool
    {
        if (empty($lump_name)) { return false; }

        $lump_name = strtolower($lump_name);

        //If we have no lump by this name recorded, add it
        if (!isset($this->reservedLumps[$lump_name])) {
            $this->reservedLumps[$lump_name] = new ReservedLump($lump_name, $attemptingMapRampId, $data_hash);
            return true;
        }
        //We already had a lump with this name. Look to see if it belongs to our base resources or a map, and if it's identical to the existing one
        //IWAD overwrites are never allowed
        if ($this->reservedLumps[$lump_name]->ownerRampId == ReservedLump::LUMP_OWNER_IWAD) {
            $this->rejectedLumps[] = new RejectedLump($lump_name, $this->reservedLumps[$lump_name]->ownerRampId, $attemptingMapRampId, $data_hash);
            Logger::pg(get_error_link('ERR_LUMP_DUPLICATE_BASE', [$lump_name]), $attemptingMapRampId, true);
            return false;
        }
        
        //If the hash matches, notify but don't count as error
        $existing_hash = $this->reservedLumps[$lump_name]->definition;
        if ($existing_hash == $data_hash) {
            Logger::pg(get_error_link('WARN_LUMP_DUPLICATE_OTHER', [$lump_name, $this->reservedLumps[$lump_name]->ownerRampId]), $attemptingMapRampId);
            return true;
        }
        $this->rejectedLumps[] = new RejectedLump($lump_name, $this->reservedLumps[$lump_name]->ownerRampId, $attemptingMapRampId, $data_hash);
        Logger::pg(get_error_link('ERR_LUMP_DUPLICATE_OTHER', [$lump_name, $this->reservedLumps[$lump_name]->ownerRampId]), $attemptingMapRampId, true);
        return false;
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_lumps(): void
    {
        $lump_file_contents = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.lumps");
        $lump_names = explode("\n", $lump_file_contents);
        Logger::pg("Read " . count($lump_names) . " protected lumps");
        foreach ($lump_names as $lump_name) {
            $this->add_lump_to_global_list($lump_name, "", ReservedLump::LUMP_OWNER_IWAD);
        }
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_textures(): void
    {
        $texture_data = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.textures");
        Logger::pg("Adding base textures to global texture list");
        $this->validate_textures($texture_data, ReservedLump::LUMP_OWNER_IWAD);
    }

    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_sound_sequences(): void
    {
        $sndseq = file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.sndseq");
        Logger::pg("Adding base sound sequences to global sound sequence list");
        $this->add_sound_sequences($sndseq, ReservedLump::LUMP_OWNER_IWAD);
    }
    
    //TODO Make this customizable, just does Doom2 just now
    private function add_doom2_sound_definitions(): void
    {
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

    private function set_up_reserved_ranges()
    {
        $this->reservedDoomEdNumRanges = [
            new ReservedDoomEdNumRange(1,127,"Reserved"),
            new ReservedDoomEdNumRange(888,888,"MBFHelperDog"),
            new ReservedDoomEdNumRange(1200 ,1209,"Heretic sound sequences"),
            new ReservedDoomEdNumRange(1400 ,1411,"Hexen sound sequences"),
            new ReservedDoomEdNumRange(1500 ,1505,"Geometry objects"),
            new ReservedDoomEdNumRange(2001 ,2049,"Doom objects"),
            new ReservedDoomEdNumRange(3001 ,3006,"Doom monsters"),
            new ReservedDoomEdNumRange(4001 ,4004,"Additional playerstarts"),
            new ReservedDoomEdNumRange(4500 ,4503,"Mine Lamps"),
            new ReservedDoomEdNumRange(5001 ,5010,"Reserved"),
            new ReservedDoomEdNumRange(5050 ,5050,"Stalagmite"),
            new ReservedDoomEdNumRange(5061 ,5065,"Bridge objects"),
            new ReservedDoomEdNumRange(7000 ,7000,"Grass"),
            new ReservedDoomEdNumRange(7100 ,7120,"Flora"),
            new ReservedDoomEdNumRange(9024 ,9048,"Misc script objects"),
            new ReservedDoomEdNumRange(9050 ,9083,"Stealth monsters that nobody likes"),
            new ReservedDoomEdNumRange(9100 ,9111,"Scripted marines"),
            new ReservedDoomEdNumRange(9200 ,9200,"Decal"),
            new ReservedDoomEdNumRange(9300 ,9303,"Polyobjects"),
            new ReservedDoomEdNumRange(9500 ,9511,"Ramps"),
            new ReservedDoomEdNumRange(9600 ,9632,"Wolf3D objects"),
            new ReservedDoomEdNumRange(9702 ,9724,"Trees"),
            new ReservedDoomEdNumRange(9800 ,9830,"Lights"),
            new ReservedDoomEdNumRange(9901 ,9930,"Hub objects"),
            new ReservedDoomEdNumRange(9980 ,9999,"Event objects"),
            new ReservedDoomEdNumRange(14001 ,14067,"Sound objects"),
            new ReservedDoomEdNumRange(14101 ,14165,"Music changers"),
            new ReservedDoomEdNumRange(32000 ,32000,"Doom Builder Camera")
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'doomEdNums' => $this->doomEdNums,
            'spawnNums' => $this->spawnNums,
            'rejectedDoomEdNums' => $this->rejectedDoomEdNums,
            'rejectedSpawnNums' => $this->rejectedSpawnNums,
            'mapsWithRejectedScripts' => $this->rampIdsWithRejectedScripts,
            'globalAmbientList' => $this->global_ambient_list,
            'rejectedLumps' => $this->rejectedLumps,
        ];
    }

}
