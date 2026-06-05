<?php
class GlDefsProcessor extends LumpProcessor {

    public function process(): bool
    {
        //Extract and load brightmap lumps
        $matches = [];
        preg_match_all('/[^0-9A-Za-z]+?map ([0-9A-Za-z]*)/im', $this->lump->data, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $brightmapLumpName = $matches[1][$i];
            //Get this lump from our current WAD and treat it as a graphic, if this lump isn't already there
            $brightmapLump = $this->wad->get_lump($brightmapLumpName);
            if (!$brightmapLump) {
                Logger::pg("Couldn't find brightmap " . $brightmapLumpName . " to import, trusting it's already included", $this->rampMap->rampId);
                continue;
            }
            if (!$this->lumpRegistry->reserveLump($brightmapLump, $this->rampMap->rampId)) {
                continue;
            }
            Logger::pg("Adding brightmap " . $brightmapLumpName . " as a graphic", $this->rampMap->rampId);
            $graphics_folder = PK3_FOLDER . "graphics";
            @mkdir($graphics_folder, 0755, true);
            $graphic_file_path = PK3_FOLDER . "graphics/" . $brightmapLumpName;
            file_put_contents($graphic_file_path, $brightmapLump->data);
        }
        $this->accept();
        return true;
    }
}
