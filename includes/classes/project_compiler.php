<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Project_Compiler {

    public array $map_additional_mapinfo = [];

    public Music_Lump_Mapper $music_lump_mapper;
    public LumpRegistry $lumpRegistry;

    public Catalog_Handler $catalog_handler;
    public Build_Numberer $build_numberer;
    
    public static string $decorate_id_number_prefix = "DECORATE class:";

    public function __construct() {
        $this->lumpRegistry = new LumpRegistry();
        $this->catalog_handler = new Catalog_Handler();
        $this->build_numberer = new Build_Numberer();
        $this->music_lump_mapper = new Music_Lump_Mapper();
    }

    function compile_project($prepare_file = true): bool {

        Logger::pg("Adding base spawnnums to global list");
        $lines = explode("\n", file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.spawnnums"));
        Logger::pg("Read " . count($lines) . " protected spawnnums");
        foreach ($lines as $line) {
            $elements = explode('=', trim($line));
            $spawn_num = strtolower(trim($elements[0]));
            $classname = strtolower(trim($elements[1]));
            if ($classname != "") {
                $this->lumpRegistry->reserveSpawnNumber($spawn_num, 0, $classname);
            }
        }

        //Begin!
        $start_time = time();
        $milestone_times = [];
        Logger::clear_pk3_log();
        $this->set_status("Initializing");
        if (!wait_for_lock(LOCK_FILE_COMPILE)) {
            return false;
        }
        @mkdir(get_setting("PROJECT_OUTPUT_FOLDER"), 0777, true);

        $new_build_number = $this->build_numberer->get_current_build() + 1;
        Logger::pg("Locked for generating new download, build number " . $new_build_number);

        $this->set_status("Clearing old work folder");
        $this->clean();
        $milestone_times[] = time() - $start_time; //Preparation

        $this->set_status("Translating uploaded WADs into maps...");
        $rejected_map_ramp_ids = $this->generate_map_wads();
        $this->set_status("Generating MAPINFO and other descriptors like that...");
        $this->generate_info($rejected_map_ramp_ids);
        $milestone_times[] = time() - $start_time; //Map compiling

        //The rest of these actions can be skipped if we're not preparing the final file
        if ($prepare_file) {
            $this->set_status("Copying static content like the hub map and textures...");
            $this->copy_static_content();
            $milestone_times[] = time() - $start_time; //Copying static content

            $this->set_status("Fiddling with DIALOGUE to write guide menus...");
            $this->write_guide_dialogue();
            if (get_setting("GENERATE_MARQUEES")) {
                $this->set_status("Generating marquee textures...");
                $generator = new Marquee_Generator($this->catalog_handler);
                $generator->includeMarquees();
            }
            file_put_contents(PK3_FOLDER . DIRECTORY_SEPARATOR . "RVERSION", "Build number " . $new_build_number . ", built: " . date("F j, Y, g:i a T", $start_time));
            $milestone_times[] = time() - $start_time; //Generating hub resources
            if (get_setting("PROJECT_FORMAT") == "WAD") {
                $this->set_status("Compiling WAD...");
                $this->create_wad();
            } else {
                $this->set_status("Packing PK3...");
                $this->create_pk3();
            }
        }
        //Unmutex
        release_lock(LOCK_FILE_COMPILE);
        $this->set_status("Complete");
        Logger::pg("Download generating lock released");
        $seconds = time() - $start_time;
        $milestone_times[] = $seconds; //Finish
        Logger::pg("Project generated in " . $seconds . " seconds, build number " . $new_build_number);
        Logger::record_pk3_generation($start_time, $milestone_times);
        $this->build_numberer->set_new_build_number($new_build_number);
        Logger::save_build_info($this->lumpRegistry);
        return true;
    }

    function write_guide_dialogue(): void
    {
        Logger::pg("Processing hub WAD");
        if (!get_setting('GUIDE_ENABLED')) {
            Logger::pg("Guide is not enabled, not touching anything");
            return;
        }

        $hub_map_location = PK3_FOLDER . DIRECTORY_SEPARATOR . "maps" . DIRECTORY_SEPARATOR . get_setting("GUIDE_MAP_NAME") . ".wad";
        $wad_in = new Wad_Handler($hub_map_location);
        $wad_out = new Wad_Handler();

        if (!$wad_in->count_lumps()) {
            Logger::pg("WAD for guide script " . get_setting("GUIDE_MAP_NAME") . ".wad not found" . " - skipping");
            return;
        }
        
        //Go through our uploaded WAD and copy all the lumps. Inject our DIALOGUE after BEHAVIOR, or append it to existing one
        $has_existing_dialogue = $wad_in->get_lump("DIALOGUE");
        foreach ($wad_in->lumps as $lump) {
            Logger::pg("Got lump " . $lump->name . " from guide target WAD");
            if ($lump->name == 'DIALOGUE' && $has_existing_dialogue) {
                Logger::pg("Appending generated DIALOGUE to existing lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $lump->data .= PHP_EOL . $guide_writer->write($has_existing_dialogue);
            }
            $wad_out->add_lump($lump);
            if ($lump->name == 'BEHAVIOR' && !$has_existing_dialogue) {
                Logger::pg("Inserting generated DIALOGUE lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $dialogue_lump = new Lump('DIALOGUE', 0, 0, $guide_writer->write($has_existing_dialogue));
                $wad_out->add_lump($dialogue_lump);
            }            
        }
        Logger::pg("Writing new guide WAD");
        $bytes_written = $wad_out->write_wad($hub_map_location);
        Logger::pg("Wrote " . $bytes_written . " bytes to new guide WAD");
    }
    
    function generate_map_wads() : array {
        Logger::pg("--- IMPORTING MAPS AND RESOURCES FROM UPLOADED WADS ---");
        $catalog = $this->catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;
        $rejected_map_ramp_ids = [];
        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Translating uploaded WADs into maps... " . $map_index . "/" . $total_maps);
            Logger::pg("-------------------------------------------------------");

            if ($map_data->category == '') {
                Logger::pg("Skipping provisional map slot. This map will be included after the author makes the first upload.", $map_data->rampId, true);
                $rejected_map_ramp_ids[] = $map_data->rampId;
                continue;
            }
            $map_file_name = get_source_wad_file_name($map_data->rampId);

            Logger::pg(PHP_EOL . "📦 " .
                "Reading source WAD " . $map_file_name . " for " . $map_data->lump . ": " . $map_data->name . " 📦",
                $map_data->mapnum
            );
            
            if (($map_data->disabled ?? 0) > 0) {
                Logger::pg(get_error_link('ERR_DISABLED'), $map_data->rampId, true);
                $rejected_map_ramp_ids[] = $map_data->rampId;
                continue;
            }

            $source_wad = UPLOADS_FOLDER . $map_file_name;
            $wad_handler = new Wad_Handler($source_wad);
            if (!$wad_handler->count_lumps()) {
                Logger::pg(get_error_link('ERR_WAD_MISSING', [$map_file_name]), $map_data->rampId, true);
                $rejected_map_ramp_ids[] = $map_data->rampId;
                continue;
            }

            Logger::pg($wad_handler->wad_info(), $map_data->rampId);
            
            $this->warn_unsupported_lumps($map_data, $wad_handler);

            if (!$this->validate_sprites($map_data, $wad_handler, ['S_START', 'SS_START'], ['S_END', 'SS_END'])) { $rejected_map_ramp_ids[] = $map_data->rampId; continue; }
            if (!(new MapProcessor($wad_handler, $this->lumpRegistry, $map_data))->process()) { $rejected_map_ramp_ids[] = $map_data->rampId; continue; }

            $this->import_between_markers($map_data, $wad_handler, ['P_START', 'PP_START', 'PPSTART'], ['P_END', 'PP_END', 'PPEND'], 'patches', 'patch');
            $this->import_between_markers($map_data, $wad_handler, ['TX_START'], ['TX_END'], 'textures', 'texture');
            $this->import_between_markers($map_data, $wad_handler, ['VX_START'], ['VX_END'], 'voxels', 'voxel');
            $this->import_between_markers($map_data, $wad_handler, ['FF_START', 'F_START'], ['FF_END', 'F_END'], 'flats', 'flat');
            $this->import_between_markers($map_data, $wad_handler, ['S_START', 'SS_START'], ['S_END', 'SS_END'], 'sprites', 'sprite');
            $this->import_between_markers($map_data, $wad_handler, ['MS_START'], ['MS_END'], 'music', 'music');
            $this->import_between_markers($map_data, $wad_handler, ['MQ_START'], ['MQ_END'], 'models', 'iqm model', '.iqm');
            $this->import_between_markers($map_data, $wad_handler, ['MD_START'], ['MD_END'], 'models', 'md3 model', '.md3');
            $this->import_between_markers($map_data, $wad_handler, ['MO_START'], ['MO_END'], 'models', 'obj model', '.obj');
            $this->import_between_markers($map_data, $wad_handler, ['MT_START'], ['MT_END'], 'models', 'png texture', '.png');
            $this->import_between_markers($map_data, $wad_handler, ['FX_START'], ['FX_END'], 'fx', 'shaders', '.glsl');
            $this->process_lumps($map_data, $wad_handler);

            (new DefaultMusicProcessor($wad_handler, $this->lumpRegistry, $map_data, $this->music_lump_mapper))->process();
            $this->import_mapinfo($map_data, $wad_handler);

            Logger::pg("Finished processing map " . $map_data->lump, $map_data->rampId);
        }

        return $rejected_map_ramp_ids;
    }
    
    function warn_unsupported_lumps(RampMap $map_data, Wad_Handler $wad_handler): void {
        $rampId = $map_data->rampId;
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump->name, ['TEXTURE1', 'TEXTURE2'])) {
                Logger::pg(get_error_link('ERR_LUMP_NEEDS_CONVERTED', ['TEXTUREx', 'TEXTURES']), $rampId, true);
            }
            if (in_array($lump->name, ['ANIMATED', 'SWITCHES', 'SWANTBLS'])) {
                Logger::pg("❌ " . $lump->name . " lumps are unsupported - convert to ANIMDEFS with SLADE3", $rampId, true);
            }
            if (in_array($lump->name, ['PLAYPAL', 'COLORMAP'])) {
                Logger::pg("❌ " . $lump->name . " lumps are unsupported", $rampId, true);
            }
            if (in_array($lump->name, ['C_START', 'C_END'])) {
                Logger::pg("❌ " . $lump->name . ": Colormaps are unsupported", $rampId, true);
            }
            if ($lump->hasLoadError) {
                Logger::pg("❌ " . $lump->name . ": failed to load", $rampId, true);
            }
        }
    }

    function validate_sprites($map_data, $wad_handler, $start_names, $stop_names): bool {
        $in_zone = "";
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump->name, $start_names)) {
                $in_zone = $lump->name;
                Logger::pg("🔽 Found starting marker " . $lump->name . " for sprites", $map_data->rampId);
                continue;
            }
            if ($in_zone && in_array($lump->name, $stop_names)) {
                Logger::pg("🔼 Found ending marker " . $lump->name . " for sprites", $map_data->rampId);
                $in_zone = "";
                continue;
            }
            if ($in_zone && $lump->data) {
                if (!preg_match('/^[a-z0-9]{4}[a-z\[\]\\\^][0-9a-f]([a-z\[\]\\\^][0-9a-f])?$/im', $lump->name)) {
                    Logger::pg("❌ Encountered bad sprite name " . $lump->name . " between sprite markers - is this really a sprite?", $map_data->rampId, true);
                    return false;
                }
            }
        }
        //If we're still in the zone at the end, something went wrong
        if ($in_zone != "") {
            Logger::pg("❌ Didn't find an end lump after " . $in_zone . " - expected one of: " . implode(", ", $stop_names) . ". This might have caused further problems", $map_data->rampId, true);
            return false;
        }
        return true;
    }
    
    function import_between_markers(RampMap $map_data, Wad_Handler $wad_handler, $start_names, $stop_names, $folder_name, $type_to_display, $filename_extension = NULL): void {
        $in_zone = false;
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump->name, $start_names)) {
                $in_zone = true;
                Logger::pg("🔽 Found starting marker for " . $folder_name, $map_data->rampId);
                continue;
            }
            if ($in_zone && in_array($lump->name, $stop_names)) {
                Logger::pg("🔼 Found ending marker for " . $folder_name, $map_data->rampId);
                $in_zone = false;
                continue;
            }
            if ($in_zone && $lump->data) {
                if ($this->lumpRegistry->nameIsInSpecialLumpList($lump)) {
                    continue;
                }
                if (!$this->lumpRegistry->reserveLump($lump, $map_data->rampId)) {
                    continue;
                }
                $lump_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . $folder_name . DIRECTORY_SEPARATOR . $map_data->lump . DIRECTORY_SEPARATOR;
                @mkdir($lump_folder, 0755, true);
                $output_file = $lump_folder . DIRECTORY_SEPARATOR . get_safe_lump_file_name($lump->name) . $filename_extension;
                try {
                    if (@file_put_contents($output_file, $lump->data) === false) {
                        throw new Exception("Unable to write to output file " . $output_file);
                    }
                } catch (Exception $e) {
                    Logger::pg("❌ Failed to write file " . $output_file . " for map " . $map_data->rampId . ": " . $e->getMessage(), $map_data->rampId, true);
                }
                Logger::pg("Wrote " . $type_to_display . " " . $output_file, $map_data->rampId);
            }
        }
        //If we're still in the zone at the end, something went wrong
        if ($in_zone) {
            Logger::pg("❌ Didn't find an end lump for " . $folder_name . " - expected one of: " . implode(", ", $stop_names) . ". This might have caused further problems", $map_data->rampId, true);
        }
    }
    
    function process_lumps(RampMap $map_data, Wad_Handler $wad_handler): void {

        foreach ($wad_handler->lumps as $index => $lump) {
            $lumpProcessorClassName = null;
            if ($lump->type == 'font') {
                $lumpProcessorClassName = 'FontProcessor';
            }
            $lumpProcessorClassName = $lumpProcessorClassName ?? match ($lump->name) {
                'LOCKDEFS' => 'LockDefsProcessor',
                'TEXTURES' => 'TexturesProcessor',
                'SNDINFO' => 'SndInfoProcessor',
                'SNDSEQ' => 'SndSeqProcessor',
                'GLDEFS' => 'GlDefsProcessor',
                'TEXTCOLO' => 'TextColoProcessor',
                'DECORATE', 'ZSCRIPT' => 'ScriptsProcessor',
                'MODELDEF' => 'ModelDefsProcessor',
                'ANIMDEFS', 'TERRAIN', 'README', 'MANUAL', 'VOXELDEF', 'SPWNDATA', 'DECALDEF', 'TRNSLATE' => 'LumpProcessor',
                default => null
            };

            if ($lumpProcessorClassName) {
                (new $lumpProcessorClassName($wad_handler, $this->lumpRegistry, $map_data, $index, $lump))->process();
            }
        }
    }
    
    function import_mapinfo($map_data, $wad_handler) {
        foreach ($wad_handler->lumps as $lump) {
            if (in_array(strtoupper($lump->name), ['MAPINFO', 'ZMAPINFO', 'UMAPINFO'])) {
                Logger::pg("📜 Found map info " . $lump->name . " lump, parsing it", $map_data->rampId);
                $mapinfo_handler = new Mapinfo_Handler($lump->data);
                $mapinfo_properties = $mapinfo_handler->parse();
                if (isset($mapinfo_properties['error'])) {
                    Logger::pg("❌ " . $mapinfo_properties['error'], $map_data->rampId, true);
                    continue;
                }
                // Doomednums will all be added at the end - put them into a global array as we encounter them
                if ($this->lumpRegistry->mapHasRejectedScript($map_data->rampId)) {
                    Logger::pg("❌ Not importing identifiers because scripts for this map were rejected", $map_data->rampId, true);
                }
                else {
                    if (isset($mapinfo_properties['doomednums'])) {
                        foreach($mapinfo_properties['doomednums'] as $number => $classname) {
                            $this->lumpRegistry->reserveDoomEdNumber($number, $map_data->rampId, $classname);
                        }
                    }
                    if (isset($mapinfo_properties['spawnnums'])) {
                        foreach($mapinfo_properties['spawnnums'] as $number => $classname) {
                            $this->lumpRegistry->reserveSpawnNumber($number, $map_data->rampId, $classname);
                        }
                    }
                }
                // For any other index-value pair, as long as it's a string, check it against the allowed properties and add it if it's OK
                foreach ($mapinfo_properties as $index => $value) {
                    if (!is_string($value)) {
                        continue;
                    }
                    Logger::pg("\t" . $index . ": " . $value, $map_data->rampId);
                    if(in_array($index, get_setting("PROJECT_ALLOWED_MAPINFO_PROPERTIES"))) {
                        $this->write_map_variable($map_data->rampId, $index, $value);
                        Logger::pg("Found custom allowable MAPINFO property " . $index . " - adding value " . $value . " to custom properties array", $map_data->rampId);
                    }
                }

                //Check for SKY1 and SKY2
                for ($i = 1; $i <= 2; $i++) {
                    if (isset($mapinfo_properties['sky' . $i])) {
                        $skyLumpName = $mapinfo_properties['sky' . $i];
                        Logger::pg("⛅ SKY" . $i . " property found in MAPINFO: " . $skyLumpName, $map_data->rampId);
                        //If the sky has been provided in the WAD, copy it in for this map!
                        if ($skylump = $wad_handler->get_lump($skyLumpName)) {
                            Logger::pg("⛅ Found lump " . $skyLumpName . " pointed to by SKY" . $i . ", including it", $map_data->rampId);
                            $skyfile = $this->write_sky_to_pk3($map_data->rampId, $i, $skylump->data);
                            $this->write_map_variable($map_data->rampId, 'sky' . $i, $skyfile); // Set the sky file name decided on by the sky writer
                        } else {
                            Logger::pg("No lump " . $skyLumpName . " pointed to by SKY" . $i . ", trusting it's already included", $map_data->rampId);
                            $this->write_map_variable($map_data->rampId, 'sky' . $i, $skyLumpName); // Just keep the existing name
                        }
                    }
                }
            }
            //If we have an entry that matches the default sky lump, use that as sky
            if (!empty(get_setting("DEFAULT_SKY_LUMP")) && (strtoupper($lump->name) == strtoupper(get_setting("DEFAULT_SKY_LUMP")))) {
                Logger::pg("⛅ " . get_setting("DEFAULT_SKY_LUMP") . " default sky lump found with size " . strlen($lump->data) . " - including it", $map_data->rampId);
                $skyfile = $this->write_sky_to_pk3($map_data->rampId, 1, $lump->data);
                if (!isset($this->map_additional_mapinfo[$map_data->rampId])) { $this->map_additional_mapinfo[$map_data->rampId] = []; }
                $this->map_additional_mapinfo[$map_data->rampId]['sky1'] = $skyfile;
            }
        }
    }
    
    function write_map_variable($map_number, $key, $value) {
        if (!isset($this->map_additional_mapinfo[$map_number])) { $this->map_additional_mapinfo[$map_number] = []; }
        $this->map_additional_mapinfo[$map_number][$key] = $value;
    }

    /**
     * Writes the MAPINFO, LANGUAGE and other data lumps using the map properties and whether we've found music, skies, etc
     */
    function generate_info(array $rejected_map_ramp_ids) : void {
        
        Logger::pg("--- GENERATING INFO LUMPS ---");
        
        $catalog = $this->catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;

        $mapinfo = "";
        $language = "[enu default]" . PHP_EOL . PHP_EOL;
        $ramp_data = [];

        $write_mapinfo = get_setting("PROJECT_WRITE_MAPINFO");

        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Generating MAPINFO and other descriptors like that... " . $map_index . "/" . $total_maps);

            //Skip acknowledging this map if it was rejected
            if (in_array($map_data->rampId, $rejected_map_ramp_ids)) {
                Logger::pg("Skipping writing metadata for rejected map " . $map_data->lump . " with id " . $map_data->rampId);
                continue;
            }

            //If our global settings don't allow jump, remove the RJUMP flag here
            if (get_setting("ALLOW_GAMEPLAY_JUMP") == 'never') {
                $map_data->removeFlag(RampMap::FLAG_JUMP);
            }
            if (get_setting("ALLOW_GAMEPLAY_JUMP") == 'always') {
                $map_data->addFlag(RampMap::FLAG_JUMP);
            }

            $language .= $map_data->lump . "NAME = \"" . $map_data->name . "\";" . PHP_EOL;
            $language .= $map_data->lump . "AUTH = \"" . $map_data->author . "\";" . PHP_EOL;
            $language .= $map_data->lump . "MUSC = \"" . ($map_data->musicCredit ?? '') . "\";" . PHP_EOL;
            $language .= PHP_EOL;

            $ramp_data[$map_data->rampId] = implode(",",
                [
                    $map_data->mapnum,
                    $map_data->lump,
                    $map_data->length ?? 0,
                    $map_data->difficulty ?? 0,
                    $map_data->monsters ?? 0,
                    $map_data->category ?? '',
                    implode(":", $map_data->getFlags()),
                    Logger::map_has_errors($map_data->rampId),
                    $map_data->name
                ]
            );

            if (!$write_mapinfo) {
                continue;
            }
            
            //Header
            $mapinfo .= "map " . $map_data->lump . " lookup " . $map_data->lump . "NAME" . PHP_EOL;
            
            //The basics - include the name, author, and point everything to go back to the hub/intermission map
            $mapinfo .= "{" . PHP_EOL;
            $mapinfo .= "\t" . "author = \"$" . $map_data->lump . "AUTH\"" . PHP_EOL;
            $mapinfo .= "\t" . "levelnum = " . $map_data->mapnum . PHP_EOL;
            $mapinfo .= "\t" . "cluster = 1" . PHP_EOL; // Why don't these inherit?
            $mapinfo .= "\t" . "NoIntermission" . PHP_EOL;

            //Include any allowed custom properties from original upload
            Logger::pg("Checking for custom properties for map " .$map_data->lump, $map_data->rampId);
            if (isset($this->map_additional_mapinfo[$map_data->rampId])) {
                $custom_properties = $this->map_additional_mapinfo[$map_data->rampId];
                foreach ($custom_properties as $index => $property) {
                    if ($index == 'music') {
                        continue; //We'll handle music specifically later
                    }
                    Logger::pg("Including custom property " . $index . " as " . $property, $map_data->rampId);
                    if ($property == '_SET_') { //Used to flag properties that take no value
                        $mapinfo .= "\t" . $index . PHP_EOL;
                    } else {
                        $mapinfo .= "\t" . $index . " = " . $property . PHP_EOL;
                    }
                }
            }
            
            //Skies. If we haven't got a specific one (which will have been added by the custom properties above),
            //check for a sky written to the folder, then fall back to default
            if (!isset($this->map_additional_mapinfo[$map_data->rampId]['sky1'])) {
                Logger::pg("No sky1 set, falling back to SKY1 for map " . $map_data->lump, $map_data->rampId);
                $mapinfo .= "\t" . "sky1 = SKY1" . PHP_EOL;
            }

            //Use this map's music if we've already parsed it out. If not, try the music in the custom properties. Then fall back to our default
            $expected_music_lump = $this->music_lump_mapper->get_name_for_music_lump($map_data->lump);
            $expected_music_file = PK3_FOLDER . "music" . DIRECTORY_SEPARATOR . $expected_music_lump;
        
            if (file_exists($expected_music_file)) {
                Logger::pg("Using music lump " . $expected_music_lump . " for map " . $map_data->lump, $map_data->rampId);
                $mapinfo .= "\t" . "music = " . $expected_music_lump . PHP_EOL;
            } else if (isset($this->map_additional_mapinfo[$map_data->rampId]['music'])) {
                $music_lump = $this->map_additional_mapinfo[$map_data->rampId]['music'];
                Logger::pg("Using specific music lump " . $music_lump . " for map " . $map_data->lump, $map_data->rampId);
                $mapinfo .= "\t" . "music = " . $music_lump . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "music = " . get_setting("DEFAULT_MUSIC_LUMP") . PHP_EOL;
            }

            if ($map_data->hasFlag(RampMap::FLAG_JUMP)) {
                $mapinfo .= "\t" . "AllowJump" . PHP_EOL;
                $mapinfo .= "\t" . "AllowCrouch" . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "NoJump" . PHP_EOL;
                $mapinfo .= "\t" . "NoCrouch" . PHP_EOL;
            }

            //Finally, include properties specified by map data.
            $mapinfo .= ($map_data->mapInfoString);
            $postLevelMapLumpName = get_setting("POST_LEVEL_MAP_NAME");
            if ($postLevelMapLumpName) {
                $mapinfo .= "\t" . "next = {$postLevelMapLumpName}" . PHP_EOL;
                $mapinfo .= "\t" . "secretnext = {$postLevelMapLumpName}" . PHP_EOL;
            }

            $mapinfo .= "}" . PHP_EOL;
            $mapinfo .= PHP_EOL;
        }
        
        // If custom identifiers were found during WAD creation, add them to the global MAPINFO now

        if ($this->lumpRegistry->hasDoomEdNumbers()) {
            $mapinfo .= "doomednums {" . PHP_EOL;
            foreach ($this->lumpRegistry->getDoomEdNumbers() as $reservedDoomEdNumber) {
                //If this DoomEd number was defined by Decorate, it doesn't need to be included in this list - it will already have been parsed
                if (str_starts_with($reservedDoomEdNumber->className, self::$decorate_id_number_prefix)) {
                    continue;
                }
                $mapinfo .= "    " . $reservedDoomEdNumber->number . " = " . "\"" . $reservedDoomEdNumber->className . "\"" . " // Ramp ID " . $reservedDoomEdNumber->ownerRampId . PHP_EOL;
            }
            $mapinfo .= "}" . PHP_EOL;
        }
        if ($this->lumpRegistry->hasSpawnNumbers()) {
            $mapinfo .= "spawnnums {" . PHP_EOL;
            foreach ($this->lumpRegistry->getSpawnNumbers() as $reservedSpawnNumber) {
                if (str_starts_with($reservedSpawnNumber->className, self::$decorate_id_number_prefix)) {
                    continue;
                }
                $mapinfo .= "    " . $reservedSpawnNumber->number . " = " . "\"" . $reservedSpawnNumber->className . "\"" . " // Ramp ID " . $reservedSpawnNumber->ownerRampId . PHP_EOL;
            }
            $mapinfo .= "}" . PHP_EOL;
        }
        
        //All done - output the files
        $language_filename = PK3_FOLDER . "LANGUAGE.rampart";
        @unlink($language_filename);
        file_put_contents($language_filename, $language);
        Logger::pg("Wrote " . $language_filename);
        
        if (!$write_mapinfo) {
            return;
        }

        $mapinfo_filename = PK3_FOLDER . "MAPINFO.rampart";
        @unlink($mapinfo_filename);
        file_put_contents($mapinfo_filename, $mapinfo);
        Logger::pg("Wrote " . $mapinfo_filename);
        
        ksort($ramp_data);
        $rampdata_filename = PK3_FOLDER . "RAMPDATA.rampart";
        @unlink($rampdata_filename);
        file_put_contents($rampdata_filename, implode(PHP_EOL, $ramp_data));
        Logger::pg("Wrote " . $rampdata_filename);
    }

    function write_sky_to_pk3($mapnum, $skynum, $sky_bytes) {
        $skyFileName = "MSKY";
        if ($skynum == 2) {
            $skyFileName = "MSKZ";
        }
        $skyFileName .= $mapnum;
        $folder = PK3_FOLDER . "textures/MAP" . $mapnum;
        @mkdir($folder, 0755, true);
        $sky_file_path = $folder . "/" . $skyFileName;
        file_put_contents($sky_file_path, $sky_bytes);
        Logger::pg("Wrote " . strlen($sky_bytes) . " bytes to " . $sky_file_path);
        return $skyFileName;
    }
    
    function create_wad() {
        Logger::pg("--- WRITING WAD ---");
        $wad_out = new Wad_Handler();
        
        //Include contents of any resource WADs
        if (file_exists(RESOURCE_WAD_FOLDER)) {
            $resource_wads = scandir(RESOURCE_WAD_FOLDER);
            foreach($resource_wads as $resource_wad) {
                if (!is_file(RESOURCE_WAD_FOLDER . $resource_wad) || str_starts_with($resource_wad, ".")) {
                    continue;
                }
                $wad_in = new Wad_Handler(RESOURCE_WAD_FOLDER . $resource_wad);
                foreach($wad_in->lumps as $lump) {
                    Logger::pg("Including resource WAD " . $resource_wad . "->" . $lump->name);
                    $wad_out->add_lump($lump);
                }
            }
        }
        
        // MAPS
        
        $files = scandir(MAPS_FOLDER);
        foreach($files as $file) {
            if (!is_file(MAPS_FOLDER . $file) || str_starts_with($file, ".")) {
                continue;
            }
            $wad_in = new Wad_Handler(MAPS_FOLDER . $file);
            foreach($wad_in->lumps as $lump) {
                Logger::pg("Including " . $file . "->" . $lump->name);
                //If this is our map marker, the lump name in the WAD must be the file name!
                if ($lump->type == "mapmarker") {
                    $lump->name = substr($file, 0, strpos($file, "."));
                }
                $wad_out->add_lump($lump);
            }
        }
        
        // OTHER STUFF
        
        $this->incorporate_lumps($wad_out, "graphics");
        $this->incorporate_lumps($wad_out, "acs");
        $this->incorporate_lumps($wad_out, "textures", "TX_START", "TX_END");
        $this->incorporate_lumps($wad_out, "music");
        $this->incorporate_lumps($wad_out, "sounds");
        $this->incorporate_lumps($wad_out, "sprites", "S_START", "S_END");
        $this->incorporate_lumps($wad_out, "decorate");
        $this->incorporate_lumps($wad_out, "zscript");
        $this->incorporate_lumps($wad_out, "flats", "FF_START", "FF_END");
        $this->incorporate_lumps($wad_out, "patches", "PP_START", "PP_END");
        
        // ROOT
        
        $files = scandir(PK3_FOLDER);
        foreach($files as $file) {
            if (!is_file(PK3_FOLDER . $file) || str_starts_with($file, ".")) {
                continue;
            }
            Logger::pg("Including from root folder: " . $file);
            $wad_out->add_lump(new Lump($this->get_lump_name_from_path($file), 0, 0, file_get_contents(PK3_FOLDER . $file)));
        }
        
        // DONE
        
        $wad_out->write_wad(get_project_full_path());
    }
    
    function incorporate_lumps($wad_out, $folder, $start_marker = null, $end_marker = null) {
        
        $rootPath = realpath(PK3_FOLDER . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR);
        if (!$rootPath) {
            Logger::pg("No " . $folder . " to include");
            return;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        //Start marker is checked in this strange way so that we only write it if we have at least one file (not folder) under consideration
        $wrote_start_marker = false;
       
        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }
            if ($start_marker && !$wrote_start_marker) {
                $wad_out->add_lump(new Lump($start_marker, 0, 0, false));
                $wrote_start_marker = true;
            }
            $lump_name = $this->get_lump_name_from_path($file->getRealPath());
            Logger::pg("Including lump for " . $folder . ": " . $lump_name);
            $filePath = $file->getRealPath();
            $lump = new Lump($lump_name, 0, 0, false);
            $lump->data = file_get_contents($filePath);
            $wad_out->add_lump($lump);
        }
        
        if ($wrote_start_marker && $end_marker) {
            $wad_out->add_lump($end_marker, 0, 0, false);
        }
    }
    
    function get_lump_name_from_path($path): string
    {
        $end_char = strpos(basename($path), ".") ?: 8;
        return strtoupper(substr(substr(basename($path), 0, $end_char), 0, 8));
    }

    function create_pk3() {
        $zip_script = $GLOBALS["ZIP_SCRIPT"];
        if (str_starts_with(PHP_OS, "WIN")) {
            $zip_script = $GLOBALS["ZIP_SCRIPT_WINDOWS"];
        }
        if (!empty($zip_script)) {
            Logger::pg("--- ASKING EXTERNAL SCRIPT TO ZIP PK3 ---");
            exec($zip_script);
            Logger::pg("Script finished");
            return;
        }
        
        //This process is much slower, but it's here for the sake of completeness
        //Use Unix's ZIP process if possible
        Logger::pg("--- CREATING PK3 ---");
        // Get real path for our folder
        $rootPath = realpath(PK3_FOLDER);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open(get_project_full_path(), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
                Logger::pg("Zipped " . $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
        Logger::pg("Wrote ZIP file");
    }
    
    function copy_static_content() {

        Logger::pg("--- COPYING STATIC CONTENT ---");

        $source = STATIC_CONTENT_FOLDER;
        $dest = PK3_FOLDER;
        if (!file_exists($source)) {
            Logger::pg("No static content folder - skipping this step");
            return;
        }

        foreach (
         $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
          \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
          if ($item->isDir()) {
            @mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            Logger::pg("Copied static content " . $item);
          }
        }
    }

    function clean(): void {
        //Guard against this being blank somehow and annihilating the server
        if (file_exists(PK3_FOLDER)) {
            $path = realpath(PK3_FOLDER);
            if (strlen($path) < 5) {
                Logger::pg("❌ Resolved path " . $path . " is fewer than five characters - aborted delete for safety!");
                return;
            }
            $this->deleteAll($path . DIRECTORY_SEPARATOR);
            Logger::pg("Cleaned target folder");
        }
        @mkdir(PK3_FOLDER);
        $path = realpath(PK3_FOLDER);
        foreach (PK3_REQUIRED_FOLDERS as $folder) {
            @mkdir($path . DIRECTORY_SEPARATOR . $folder);
        }
    }

    function deleteAll($str): bool {
        if (is_file($str)) {
            return unlink($str);
        }
        elseif (is_dir($str)) {
            $scan = glob(rtrim($str, '/').'/*');
            foreach($scan as $path) {
                $this->deleteAll($path);
            }
            return @rmdir($str);
        }
        return false;
    }

    function set_status($string): void {
        file_put_contents(STATUS_FILE, $string);
    }
}
