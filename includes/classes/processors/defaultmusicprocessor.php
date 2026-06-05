<?php
class DefaultMusicProcessor extends Processor
{
    private Music_Lump_Mapper $music_lump_mapper;
    public function __construct(Wad_Handler $wad, LumpRegistry $lumpRegistry, RampMap $rampMap, Music_Lump_Mapper $music_lump_mapper) {
        parent::__construct($wad, $lumpRegistry, $rampMap);
        $this->music_lump_mapper = $music_lump_mapper;
    }
    public function process() : bool {
        $default_music_lump_name = get_setting("DEFAULT_MUSIC_LUMP");
        $default_music_lump = $this->wad->get_lump($default_music_lump_name);
        if (!$default_music_lump) {
            Logger::pg("🎵 No default music lump " . $default_music_lump_name . " found in WAD", $this->rampMap->rampId);
            return false;
        }

        $music_type = $default_music_lump->type;
        $music_length = strlen($default_music_lump->data);
        Logger::pg("🎵 Music of type " . $music_type . " found in lump " . $default_music_lump_name . " with size " . $music_length, $this->rampMap->rampId);
        $music_path = PK3_FOLDER . "music/" . $this->music_lump_mapper->get_name_for_music_lump($this->rampMap->lump);
        file_put_contents($music_path, $default_music_lump->data);
        Logger::pg("Wrote " . $music_length . " bytes to " . $music_path, $this->rampMap->rampId);
        return true;
    }
}
