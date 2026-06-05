<?php
class SndInfoProcessor extends LumpProcessor {

    public function process() : bool {
        $sndinfo_lines_to_import = [];
        $sound_lumps_to_extract = [];
        $sndinfo_lines_to_import[] = ("// " . $this->rampMap->lump . ": " . $this->rampMap->name);

        Logger::pg("🔊 Found SNDINFO, attempting to parse it", $this->rampMap->rampId);
        $sndinfo_handler = new Sndinfo_Handler($this->lump->data, $this->rampMap->rampId);
        $sndinfo_result = $sndinfo_handler->parse();
        $requested_lump_names = $sndinfo_result['requested_lump_names'];
        $requested_definitions = $sndinfo_result['requested_definitions'];
        $requested_ambients = $sndinfo_result['requested_ambients'];
        $ambient_result = $this->lumpRegistry->add_ambients($requested_ambients, $this->rampMap->rampId);
        if ($ambient_result === false) {
            Logger::pg("❌ Not importing this SNDINFO", $this->rampMap->rampId, true);
            return false;
        }
        for ($i = 0; $i < count($requested_lump_names); $i++) {
            if (!$this->lumpRegistry->add_sndinfo_definition($requested_definitions[$i], $requested_lump_names[$i], $this->rampMap->rampId)) {
                Logger::pg("❌ Not importing this SNDINFO", $this->rampMap->rampId, true);
                return false;
            }
        }
        $sndinfo_lines_to_import = array_merge($sndinfo_lines_to_import, $sndinfo_result['input_lines']);
        $sound_lumps_to_extract = array_merge($sound_lumps_to_extract, $requested_lump_names);

        // We have the sound lumps we want to extract - let's look through and do that
        foreach ($this->wad->lumps as $lump) {
            if (in_array($lump->name, $sound_lumps_to_extract)) {
                Logger::pg("🔈 Found " . $lump->name . " mentioned in SNDINFO - assuming it's a sound", $this->rampMap->rampId);
                if (!$this->lumpRegistry->reserveLump($lump, $this->rampMap->rampId)) {
                    return false;
                }
                //This is a lump mentioned in SNDINFO! Copy it into the sounds folder
                $sound_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . "sounds" . DIRECTORY_SEPARATOR . $this->rampMap->lump;
                @mkdir($sound_folder, 0755, true);
                $sound_path = $sound_folder . DIRECTORY_SEPARATOR . $lump->name;
                file_put_contents($sound_path, $lump->data);
                Logger::pg("Wrote " . strlen($lump->data) . " bytes to " . $sound_path, $this->rampMap->rampId);
            }
        }

        //Finally, write our compiled SNDINFO
        if ($sndinfo_lines_to_import) {
            $sndinfo_filename = PK3_FOLDER . "SNDINFO." . $this->rampMap->lump . "." . $this->index;
            @unlink($sndinfo_filename);
            file_put_contents($sndinfo_filename, implode(PHP_EOL, $sndinfo_lines_to_import));
            Logger::pg("🔊 Wrote " . $sndinfo_filename, $this->rampMap->rampId);
        }
        return true;
    }
}