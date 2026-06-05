<?php
class MapProcessor extends Processor
{
    public function process() : bool {
        $in_map = false;
        $map_lumps = [];
        $first_non_map_lump_name = "";

        foreach ($this->wad->lumps as $lump) {
            //If we're in a map and this lump is not map data, we are no longer in a map!
            if ($lump->type != 'mapdata' && $in_map) {
                $first_non_map_lump_name = $lump->name;
                break;
            }
            if (($lump->type == 'mapmarker' && !$in_map) || ($lump->type == 'mapdata' && $in_map)) {
                $in_map = true;
                $map_lumps[] = $lump;
                Logger::pg("Read map lump " . $lump->name . " with size " . strlen($lump->data), $this->rampMap->rampId);
            }
        }

        if (count($map_lumps) <= 1) {
            Logger::pg(get_error_link('ERR_WAD_NO_LUMPS'), $this->rampMap->rampId, true);
            return false;
        }

        $final_map_lump_name = (end($map_lumps))->name;
        if (!in_array($final_map_lump_name, ['ENDMAP', 'BLOCKMAP'])) {
            if ($first_non_map_lump_name) {
                Logger::pg(get_error_link('ERR_WAD_BAD_LUMPS', [$first_non_map_lump_name]), $this->rampMap->rampId, true);
                return false;
            }
            Logger::pg(get_error_link('ERR_WAD_PREMATURE_END', [$final_map_lump_name]), $this->rampMap->rampId, true);

        }

        Logger::pg("Finished reading map lumps, looks like a well formed map.", $this->rampMap->rampId);

        //Construct a new WAD using only the map lumps
        $target_wad = PK3_FOLDER . "maps" . DIRECTORY_SEPARATOR . $this->rampMap->lump . ".WAD";
        Logger::pg("🗺 Writing map WAD as " . $target_wad, $this->rampMap->rampId);
        $wad_writer = new Wad_Handler();
        foreach ($map_lumps as $lump) {
            $wad_writer->add_lump($lump);
        }
        @unlink($target_wad);
        $bytes_written = $wad_writer->write_wad($target_wad);
        Logger::pg("Wrote " . $bytes_written . " bytes to " . $target_wad, $this->rampMap->rampId);
        return true;
    }
}
