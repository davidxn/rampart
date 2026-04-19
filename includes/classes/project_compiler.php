<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Project_Compiler {

    public array $map_additional_mapinfo = [];

    public Music_Lump_Mapper $music_lump_mapper;
    public Lump_Registry $lump_guardian;
    public ProjectBuildData $project_build_data;
    public Catalog_Handler $catalog_handler;
    public Build_Numberer $build_numberer;
    
    private string $decorate_id_number_prefix = "DECORATE class:";

    public function __construct() {
        $this->lump_guardian = new Lump_Registry();
        $this->catalog_handler = new Catalog_Handler();
        $this->build_numberer = new Build_Numberer();
        $this->music_lump_mapper = new Music_Lump_Mapper();
        $this->project_build_data = new ProjectBuildData($this->lump_guardian);
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
                $this->project_build_data->reserveSpawnNumber($spawn_num, 0, $classname);
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
        $this->generate_map_wads();
        $this->set_status("Generating MAPINFO and other descriptors like that...");
        $this->generate_info();
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
                $generator = new Marquee_Generator();
                $generator->generate_marquees($this->catalog_handler);
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
        Logger::save_build_info($this->project_build_data, $this->lump_guardian);
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
    
    function generate_map_wads() : void {
        Logger::pg("--- IMPORTING MAPS AND RESOURCES FROM UPLOADED WADS ---");
        $catalog = $this->catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;
        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Translating uploaded WADs into maps... " . $map_index . "/" . $total_maps);
            $map_file_name = get_source_wad_file_name($map_data->rampId);

            Logger::pg("-------------------------------------------------------");
            Logger::pg(PHP_EOL . "📦 " .
                "Reading source WAD " . $map_file_name . " for " . $map_data->lump . ": " . $map_data->name . " 📦",
                $map_data->mapnum
            );
            
            if (($map_data->disabled ?? 0) > 0) {
                Logger::pg(get_error_link('ERR_DISABLED'), $map_data->rampId, true);
                continue;
            }

            $source_wad = UPLOADS_FOLDER . $map_file_name;
            $wad_handler = new Wad_Handler($source_wad);
            if (!$wad_handler->count_lumps()) {
                Logger::pg(get_error_link('ERR_WAD_MISSING', [$map_file_name]), $map_data->rampId, true);
                continue;
            }

            Logger::pg($wad_handler->wad_info(), $map_data->rampId);
            
            $this->warn_unsupported_lumps($map_data, $wad_handler);

            if (!$this->validate_sprites($map_data, $wad_handler, ['S_START', 'SS_START'], ['S_END', 'SS_END'])) { continue; }
            if (!$this->import_map($map_data, $wad_handler)) { continue; }
            
            $this->import_sndinfo_and_sounds($map_data, $wad_handler);
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
            $this->import_modeldefs($map_data, $wad_handler);
            $this->import_lumps_directly($map_data, $wad_handler, ['TEXTURES', 'GLDEFS', 'ANIMDEFS', 'LOCKDEFS', 'SNDSEQ', 'README', 'MANUAL', 'VOXELDEF', 'TEXTCOLO', 'SPWNDATA', 'DECALDEF', 'TRNSLATE']);
            $this->import_default_music($map_data, $wad_handler);
            $this->import_scripts($map_data, $wad_handler);
            $this->import_mapinfo($map_data, $wad_handler);
            $this->import_special_lumps($map_data, $wad_handler);
        }
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
    
    function import_sndinfo_and_sounds(RampMap $map_data, Wad_Handler $wad_handler): void {
        $sndinfo_lines_to_import = [];
        $sound_lumps_to_extract = [];
        $sndinfo_lumps = $wad_handler->get_lumps("SNDINFO");
        if ($sndinfo_lumps) {
            // Add comment showing map identification
            $sndinfo_lines_to_import[] = ("// " . $map_data->lump . ": " . $map_data->name);
            
            // Get all lines we want to include
            foreach ($sndinfo_lumps as $sndinfo_lump) {
                Logger::pg("🔊 Found SNDINFO, attempting to parse it", $map_data->rampId);
                $sndinfo_handler = new Sndinfo_Handler($sndinfo_lump->data, $map_data->rampId);
                $sndinfo_result = $sndinfo_handler->parse();
                $requested_lump_names = $sndinfo_result['requested_lump_names'];
                $requested_definitions = $sndinfo_result['requested_definitions'];
                $requested_ambients = $sndinfo_result['requested_ambients'];
                $ambient_result = $this->lump_guardian->add_ambients($requested_ambients, $map_data->rampId);
                if ($ambient_result === false) {
                    Logger::pg("❌ Not importing this SNDINFO", $map_data->rampId, true);
                    continue;
                }
                for ($i = 0; $i < count($requested_lump_names); $i++) {
                    if (!$this->lump_guardian->add_sndinfo_definition($requested_definitions[$i], $requested_lump_names[$i], $map_data->rampId)) {
                        Logger::pg("❌ Not importing this SNDINFO", $map_data->rampId, true);
                        continue 2;
                    }
                }
                $sndinfo_lines_to_import = array_merge($sndinfo_lines_to_import, $sndinfo_result['input_lines']);
                $sound_lumps_to_extract = array_merge($sound_lumps_to_extract, $requested_lump_names);
            }
            
            // We have the sound lumps we want to extract - let's look through and do that
            foreach ($wad_handler->lumps as $lump) {
                if (in_array($lump->name, $sound_lumps_to_extract)) {
                    Logger::pg("🔈 Found " . $lump->name . " mentioned in SNDINFO - assuming it's a sound", $map_data->rampId);
                    if (!$this->lump_guardian->reserveLump($lump, $map_data->rampId)) {
                        continue;
                    }
                    //This is a lump mentioned in SNDINFO! Copy it into the sounds folder
                    $sound_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . "sounds" . DIRECTORY_SEPARATOR . $map_data->lump;
                    @mkdir($sound_folder, 0755, true);
                    $sound_path = $sound_folder . DIRECTORY_SEPARATOR . $lump->name;
                    file_put_contents($sound_path, $lump->data);
                    Logger::pg("Wrote " . strlen($lump->data) . " bytes to " . $sound_path, $map_data->rampId);
                }
            }
            
            //Finally, write our compiled SNDINFO
            if ($sndinfo_lines_to_import) {
                $sndinfo_filename = PK3_FOLDER . "SNDINFO." . $map_data->rampId;
                @unlink($sndinfo_filename);
                file_put_contents($sndinfo_filename, implode(PHP_EOL, $sndinfo_lines_to_import));
                Logger::pg("🔊 Wrote " . $sndinfo_filename, $map_data->rampId);
            }
        }
    }
    
    function import_map($map_data, $wad_handler): bool {
        $in_map = false;
        $map_lumps = [];
        
        foreach ($wad_handler->lumps as $lump) {
            //If we're in a map and this lump is not map data, we are no longer in a map!
            if ($lump->type != 'mapdata' && $in_map) {
                Logger::pg("Finished reading map lumps", $map_data->rampId);
                break;
            }
            if (($lump->type == 'mapmarker' && !$in_map) || ($lump->type == 'mapdata' && $in_map)) {
                $in_map = true;
                $map_lumps[] = $lump;
                Logger::pg("Read map lump " . $lump->name . " with size " . strlen($lump->data), $map_data->rampId);
            }
        }
        
        if (count($map_lumps) <= 1) {
            Logger::pg(get_error_link('ERR_WAD_NO_LUMPS'), $map_data->rampId, true);
            return false;
        }
        
        //Construct a new WAD using only the map lumps
        $target_wad = PK3_FOLDER . "maps" . DIRECTORY_SEPARATOR . $map_data->lump . ".WAD";
        Logger::pg("🗺 Writing map WAD as " . $target_wad, $map_data->rampId);
        $wad_writer = new Wad_Handler();
        foreach ($map_lumps as $lump) {
            $wad_writer->add_lump($lump);
        }
        @unlink($target_wad);
        $bytes_written = $wad_writer->write_wad($target_wad);
        Logger::pg("Wrote " . $bytes_written . " bytes to " . $target_wad, $map_data->rampId);
        return true;
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
                if ($this->lump_guardian->nameIsInSpecialLumpList($lump)) {
                    continue;
                }
                if (!$this->lump_guardian->reserveLump($lump, $map_data->rampId)) {
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
    
    function import_lumps_directly(RampMap $map_data, Wad_Handler $wad_handler, $allowed_lump_names): void {
        
        $included_lump_counts = [];
        
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump->name, $allowed_lump_names)) {
                
                //Special case - reject LOCKDEFS if it tries to clear locks
                if ($lump->name == 'LOCKDEFS' && strpos(strtolower($lump->data), 'clearlocks') !== false) {
                    Logger::pg(get_error_link('ERR_LUMP_LOCKDEFS_CLEARLOCKS'), $map_data->rampId, true);
                    continue;
                }

                // Reject lockdefs that contain at least 1 lock that overwrites a vanilla lock
                if ($lump->name == 'LOCKDEFS') {
                    $lumptxt = $lump->data;
                    $lumprgx = "/^lock\s+([0-6]|10[0-1]|129|13[0-4]|229)\s*\{/mi";
                    preg_match_all($lumprgx, $lumptxt, $matches, PREG_SET_ORDER, 0);

                    if (preg_match($lumprgx, $lumptxt)) {
                        Logger::pg(get_error_link('ERR_LUMP_LOCKDEFS_CONFLICTS'), $map_data->rampId, true);
                        continue;
                    }
                }                
                
                //Another special case - reject TEXTURES if it redefines any existent lumps
                if ($lump->name == 'TEXTURES') {
                    $texture_validation_result = $this->lump_guardian->validate_textures($lump->data, $map_data->rampId);
                    $lump->data = $texture_validation_result['cleaned_data'];
                    if (!$texture_validation_result['success']) {
                        Logger::pg(get_error_link('ERR_TEX_CONFLICTS'), $map_data->rampId, true);
                    }
                }

                if ($lump->name == 'SNDSEQ') {
                    $sndseq_result = $this->lump_guardian->add_sound_sequences($lump->data, $map_data->rampId);
                    $lump->data = $sndseq_result['cleaned_data'];
                    if (!$sndseq_result['success']) {
                        Logger::pg(get_error_link('ERR_SOUND_SNDSEQ_CONFLICTS'), $map_data->rampId, true);
                    }
                }
                
                if ($lump->name == 'GLDEFS') {
                    //Extract and load brightmaps
                    $matches = [];
                    preg_match_all('/[^0-9A-Za-z]+?map ([0-9A-Za-z]*)/im', $lump->data, $matches);
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $bmlumpname = $matches[1][$i];
                        //Get this lump from our current WAD and treat it as a graphic, if this lump isn't already there
                        $bmlump = $wad_handler->get_lump($bmlumpname);
                        if (!$bmlump) {
                            Logger::pg("Couldn't find brightmap " . $bmlumpname . " to import, trusting it's already included", $map_data->rampId);
                            continue;
                        }
                        if (!$this->lump_guardian->reserveLump($bmlump, $map_data->rampId)) {
                            continue;
                        }
                        Logger::pg("Adding brightmap " . $bmlumpname . " as a graphic", $map_data->rampId);
                        $graphics_folder = PK3_FOLDER . "graphics";
                        @mkdir($graphics_folder, 0755, true);
                        $graphic_file_path = PK3_FOLDER . "graphics/" . $bmlumpname;
                        file_put_contents($graphic_file_path, $bmlump->data);
                    }
                }
                
                Logger::pg("💾 Including " . $lump->name . " lump", $map_data->rampId);
                if (!isset($included_lump_counts[$lump->name])) {
                    $included_lump_counts[$lump->name] = 1;
                }
                else {
                    $included_lump_counts[$lump->name]++;
                }
                
                @mkdir(PK3_FOLDER, 0755, true);
                $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . $lump->name . "." . $map_data->rampId . "-" . $included_lump_counts[$lump->name];
                file_put_contents($data_path, $lump->data);
                Logger::pg("Wrote " . strlen($lump->data) . " bytes to " . $data_path, $map_data->rampId);
            }
            if ($lump->type == 'font') {
                if (!$this->lump_guardian->reserveLump($lump, $map_data->rampId)) {
                    continue;
                }
                Logger::pg("💾 Including " . $lump->name . " lump as font", $map_data->rampId);
                @mkdir(PK3_FOLDER . DIRECTORY_SEPARATOR . 'fonts', 0755, true);
                $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $lump->name . '.' . $map_data->rampId;
                file_put_contents($data_path, $lump->data);
                Logger::pg("Wrote " . strlen($lump->data) . " bytes to " . $data_path, $map_data->rampId);
            }
        }
    }

    function import_modeldefs(RampMap $map_data, Wad_Handler $wad_handler): void {

        $number_of_modeldefs = 0;

        //import MODELDEF files
        //there could be multiple files with one definition in each, or one file with multiple definitions
        //this should handle both cases -- or even a mixture of cases -- fine
        foreach ($wad_handler->lumps as $lump) {
            if ($lump->name == 'MODELDEF') {
                $number_of_modeldefs++;
                
                Logger::pg("💾 Including " . $lump->name . " lump " . $number_of_modeldefs, $map_data->rampId);

                //MODELDEF files must go in the root directory of the pk3           
                $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . $lump->name . "." . $map_data->rampId . "-" . $number_of_modeldefs;

                $modeldef_lines = explode(PHP_EOL, $lump->data);

                $modeldef_data = "";

                foreach ($modeldef_lines as $line) {

                    if(preg_match('/MODEL\s+0/i', $line)){
                        //For each model definition in a legal model definition there will always be one file starting "Model 0"
                        //We need to add one Path definition line to each model definition, matching the path to models for the map being imported
                        //So it makes sense to add it immediately prior to the "Model 0" line
                        $modeldef_data .= 'Path "models' . DIRECTORY_SEPARATOR . $map_data->lump . DIRECTORY_SEPARATOR . '"' . PHP_EOL;
                    } 

                    //Since RAMPART needs to add its own Path definition line, we ignore one the mapper has already included
                    //note that allowing the mapper to include a Path line which is ignored makes it easier for the
                    //mapper to run their map both locally for testing and as part of the built project
                    if (!preg_match('/^\s*PATH/i', $line)) {
                        $modeldef_data .= $line . PHP_EOL;
                    } 
                }

                file_put_contents($data_path, $modeldef_data);
                Logger::pg("Wrote " . strlen($modeldef_data) . " bytes to " . $data_path, $map_data->rampId);
            }
        }
    }
    
    function import_default_music(RampMap $map_data, Wad_Handler $wad_handler): void {
        $default_music_lump_name = get_setting("DEFAULT_MUSIC_LUMP");
        $default_music_lump = $wad_handler->get_lump($default_music_lump_name);
        if (!$default_music_lump) {
            Logger::pg("🎵 No default music lump " . $default_music_lump_name . " found in WAD", $map_data->rampId);
            return;
        }

        $music_type = $default_music_lump->type;
        $music_length = strlen($default_music_lump->data);
        Logger::pg("🎵 Music of type " . $music_type . " found in lump " . $default_music_lump_name . " with size " . $music_length, $map_data->rampId);
        $music_path = PK3_FOLDER . "music/" . $this->music_lump_mapper->get_name_for_music_lump($map_data->lump);
        file_put_contents($music_path, $default_music_lump->data);
        Logger::pg("Wrote " . $music_length . " bytes to " . $music_path, $map_data->rampId);
    }
    
    function import_scripts($map_data, $wad_handler): void {
        //Copy DECORATE or ZSCRIPT into files in the scripts folder, and append the name on to the include file in the root
        $lump_number = 0;
        foreach ($wad_handler->lumps as $lump) {
            if (in_array(strtoupper($lump->name), ['DECORATE', 'ZSCRIPT'])) {
                if (stripos($lump->data, "replaces") !== false) { //Okay, I don't have time to write a proper parser
                    Logger::pg("❌ Found " . $lump->name . " lump but refusing it as it performs replacements!", $map_data->rampId, true);
                    $this->project_build_data->addRejectedScript($map_data->rampId);
                    continue;
                }
                
                //If this script is DECORATE, watch out for DoomEd number definitions
                
                if (strtoupper($lump->name == 'DECORATE')) {
                    $matches = [];
                    //Oh dear god - this gets the class name and DoomEd number out of an actor definition
                    preg_match_all('/(*ANYCRLF)^\s*?actor\s+([a-zA-Z0-9_]*)\s*(?::\s*[a-zA-Z0-9_]*)?\s+([0-9]+)/im', $lump->data, $matches);
                    if (isset($matches[1])) {
                        //We have some matching DoomEd numbers - attempt to add them to the global list
                        for ($i = 0; $i < count($matches[1]); $i++) {
                            $classname = $matches[1][$i];
                            $doomed_number = $matches[2][$i];
                            $result = $this->project_build_data->reserveDoomEdNumber($doomed_number, $map_data->rampId, $this->decorate_id_number_prefix . $classname);
                            if (!$result) {
                                Logger::pg("❌ Found " . $lump->name . " lump but got a DoomEdNum conflict, not including this script", $map_data->rampId, true);
                                $this->project_build_data->addRejectedScript($map_data->rampId);
                                continue 2;
                            }
                        }
                    }
                    
                    //Same for spawn nums
                    $matches = [];
                    preg_match_all('/(*ANYCRLF)\s*?actor\s+([a-zA-Z0-9_]*)[^{]*?{[^}]*?spawnid[\s]*?([0-9]+)[\s\S]*?}/im', $lump->data, $matches);
                    if (isset($matches[1])) {
                        //We have some matching spawn numbers - attempt to add them to the global list
                        for ($i = 0; $i < count($matches[1]); $i++) {
                            $classname = $matches[1][$i];
                            $spawn_number = $matches[2][$i];
                            $result = $this->project_build_data->reserveSpawnNumber($spawn_number, $map_data->rampId, $this->decorate_id_number_prefix . $classname);
                            if (!$result) {
                                Logger::pg("❌ Found " . $lump->name . " lump but got a spawnnum conflict, not including this script", $map_data->rampId, true);
                                $this->project_build_data->addRejectedScript($map_data->rampId);
                                continue 2;
                            }
                        }
                    }
                }                
                
                Logger::pg("📜 Found " . $lump->name . " lump, adding it to our script folder", $map_data->rampId);
                $lump_number++;
                $script_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . strtoupper($lump->name);
                @mkdir($script_folder, 0755, true);
                $script_file_name = strtoupper($lump->name) . "-" . $map_data->rampId . "-" . $lump_number . ".txt";
                $script_file_path = $script_folder . DIRECTORY_SEPARATOR . $script_file_name;
                
                //If this is a ZSCRIPT file and it begins with a version declaration, we have to strip that out
                if ($lump->name == 'ZSCRIPT' && strtolower(substr($lump->data, 0, 7)) == "version") {
                    Logger::pg("Taking version declaration out of ZSCRIPT lump");
                    $first_newline_position = strpos($lump->data, PHP_EOL);
                    $lump->data = substr($lump->data, $first_newline_position);
                }
                
                file_put_contents($script_file_path, $lump->data);
                Logger::pg("Wrote " . strlen($lump->data) . " bytes to " . $script_file_path, $map_data->rampId);
                
                //If this is our first ZSCRIPT inclusion, we need to add the version declaration
                $script_include_file_path = PK3_FOLDER . strtoupper($lump->name) . ".custom";
                if (strtoupper($lump->name) == "ZSCRIPT" && !file_exists($script_include_file_path)) {
                    file_put_contents($script_include_file_path, "version \"" . get_setting("ZSCRIPT_VERSION") . "\"" . PHP_EOL . PHP_EOL);
                }
                
                file_put_contents($script_include_file_path, "#include \"" . strtoupper($lump->name) . DIRECTORY_SEPARATOR . $script_file_name . "\"" . PHP_EOL, FILE_APPEND);

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
                if ($this->project_build_data->mapHasRejectedScript($map_data->rampId)) {
                    Logger::pg("❌ Not importing identifiers because scripts for this map were rejected", $map_data->rampId, true);
                }
                else {
                    if (isset($mapinfo_properties['doomednums'])) {
                        foreach($mapinfo_properties['doomednums'] as $number => $classname) {
                            $this->project_build_data->reserveDoomEdNumber($number, $map_data->rampId, $classname);
                        }
                    }
                    if (isset($mapinfo_properties['spawnnums'])) {
                        foreach($mapinfo_properties['spawnnums'] as $number => $classname) {
                            $this->project_build_data->reserveSpawnNumber($number, $map_data->rampId, $classname);
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
                continue;
            }
        }
    }
    
    function import_special_lumps($map_data, $wad_handler) {
        foreach ($wad_handler->lumps as $lump) {
            if (in_array(strtoupper($lump->name), ['RAMPSHOT'])) {
                //This is a screenshot, so include it under the screenshots folder for processing later
                $lump_folder = WORK_FOLDER . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR;
                @mkdir($lump_folder, 0755, true);
                $output_file = $lump_folder . 'RSHOT' . $map_data->rampId;
                file_put_contents($output_file, $lump->data);
                Logger::pg("📷 Exported RAMPSHOT picture as " . $output_file, $map_data->rampId);
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
    function generate_info() {
        
        Logger::pg("--- GENERATING INFO LUMPS ---");
        
        $catalog = $this->catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;

        //For every map in the catalog, write a MAPINFO entry and LANGUAGE lump.
        $mapinfo = "";
        $language = "[enu default]" . PHP_EOL . PHP_EOL;
        $ramp_data = [];

        $allow_jump = get_setting("ALLOW_GAMEPLAY_JUMP");
        $write_mapinfo = get_setting("PROJECT_WRITE_MAPINFO");

        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Generating MAPINFO and other descriptors like that... " . $map_index . "/" . $total_maps);

            //Check flags set by user
            $map_allows_jump = 0;
            if (($allow_jump == 'always' || $map_data->jumpCrouch == 1) && $allow_jump != 'never') {
                $map_allows_jump = 1;
            }

            $language .= $map_data->lump . "NAME = \"" . $map_data->name . "\";" . PHP_EOL;
            $language .= $map_data->lump . "AUTH = \"" . $map_data->author . "\";" . PHP_EOL;
            $language .= $map_data->lump . "MUSC = \"" . ($map_data->musicCredit ?? '') . "\";" . PHP_EOL;
            $language .= PHP_EOL;

            $ramp_data[$map_data->rampId] = implode(",",
                [
                    $map_data->mapnum,
                    $map_data->lump,
                    $map_allows_jump,
                    $map_data->wip,
                    $map_data->length ?? 0,
                    $map_data->difficulty ?? 0,
                    $map_data->monsters ?? 0,
                    $map_data->category ?? '',
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
            $mapinfo .= "author = \"$" . $map_data->lump . "AUTH\"" . PHP_EOL;
            $mapinfo .= "levelnum = " . $map_data->mapnum . PHP_EOL;
            $mapinfo .= "cluster = 1" . PHP_EOL;
            
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
                Logger::pg("No sky1 set, falling back to RSKY1 for map " . $map_data->lump, $map_data->rampId);
                $mapinfo .= "\t" . "sky1 = RSKY1" . PHP_EOL;
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

            if ($map_allows_jump) {
                $mapinfo .= "\t" . "AllowJump" . PHP_EOL;
                $mapinfo .= "\t" . "AllowCrouch" . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "NoJump" . PHP_EOL;
                $mapinfo .= "\t" . "NoCrouch" . PHP_EOL;
            }

            //Finally, include properties specified by map data. If there is nothing in MAPINFO, default to setting the next level to the one defined globally
            $mapinfo .= ($map_data->mapInfoString ?? 'next = ' . get_setting("POST_LEVEL_MAP_NAME")) . PHP_EOL;

            $mapinfo .= "}" . PHP_EOL;
            $mapinfo .= PHP_EOL;
        }
        
        // If custom identifiers were found during WAD creation, add them to the global MAPINFO now

        if ($this->project_build_data->hasDoomEdNumbers()) {
            $mapinfo .= "doomednums {" . PHP_EOL;
            foreach ($this->project_build_data->getDoomEdNumbers() as $reservedDoomEdNumber) {
                //If this DoomEd number was defined by Decorate, it doesn't need to be included in this list - it will already have been parsed
                if (str_starts_with($reservedDoomEdNumber->className, $this->decorate_id_number_prefix)) {
                    continue;
                }
                $mapinfo .= "    " . $reservedDoomEdNumber->number . " = " . "\"" . $reservedDoomEdNumber->className . "\"" . " // Ramp ID " . $reservedDoomEdNumber->ownerRampId . PHP_EOL;
            }
            $mapinfo .= "}" . PHP_EOL;
        }
        if ($this->project_build_data->hasSpawnNumbers()) {
            $mapinfo .= "spawnnums {" . PHP_EOL;
            foreach ($this->project_build_data->getSpawnNumbers() as $reservedSpawnNumber) {
                if (str_starts_with($reservedSpawnNumber->className, $this->decorate_id_number_prefix)) {
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
