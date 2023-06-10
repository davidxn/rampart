<?php

require_once("_constants.php");
require_once("_functions.php");
require_once("scripts/wad_handler.php");
require_once("scripts/mapinfo_handler.php");
require_once("scripts/guide_writer.php");
require_once("scripts/catalog_handler.php");
require_once("scripts/build_numberer.php");
require_once("scripts/sndinfo_handler.php");
require_once("scripts/music_lump_mapper.php");

class Project_Compiler {

    public $map_additional_mapinfo = [];
    public $wad_variables = ['custom_defined_doomednums' => [], 'custom_defined_spawnnums' => []];
    public $global_lump_list = [];
    public $music_lump_mapper = null;
    
    function add_lump_to_global_list($lumpname, $data_hash, $owning_map) {
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
            Logger::pg("Lump " . $lumpname . " matches same name from map number " . $this->global_lump_list[$lumpname]['owner'] . ". The data is identical", $owning_map);
            return true;
        }
        
        Logger::pg("❌ Lump " . $lumpname . " would overwrite existing lump of that name from map number " . $this->global_lump_list[$lumpname]['owner'] . ". Please rename it to make sure both maps work correctly", $owning_map, true);
        return false;
    }
    
    function add_doom2_lumps_to_global_list() {
        $lumpnames = explode("\n", file_get_contents(DATA_FOLDER . DIRECTORY_SEPARATOR . "doom2.lumps"));
        Logger::pg("Found " . count($lumpnames) . " to add to global list");
        foreach ($lumpnames as $lumpname) {
            $lumpname = trim($lumpname);
            if ($lumpname != "") {
                $this->add_lump_to_global_list($lumpname, "", "IWAD");
            }
        }
    }

    function compile_project() {
        
        //Begin!
        $start_time = time();
        Logger::clear_pk3_log();
        $this->set_status("Initializing");
        file_put_contents(LOCK_FILE_COMPILE, ":)");
        @mkdir(get_setting("PROJECT_OUTPUT_FOLDER"), 0777, true);

        //Temporary: Add some DOOM2 lump names to our global list. We'll make this configurable
        $this->add_doom2_lumps_to_global_list();

        $catalog_handler = new Catalog_Handler();
        $numberer = new Build_Numberer();
        $new_build_number = $numberer->get_current_build() + 1;
        Logger::pg("Locked for generating new download, build number " . $new_build_number);

        $this->set_status("Clearing old work folder");
        $this->clean();
        $this->set_status("Translating uploaded WADs into maps...");
        $this->generate_map_wads($catalog_handler);
        $this->set_status("Generating MAPINFO and other descriptors like that...");
        $this->generate_info($catalog_handler);
        $this->set_status("Copying static content like the hub map and textures...");
        $this->copy_static_content();
        $this->set_status("Fiddling with DIALOGUE to write guide menus...");
        $this->write_guide_dialogue();
        if (get_setting("GENERATE_MARQUEES")) {
            $this->set_status("Generating marquee textures...");
            $this->generate_marquees($catalog_handler);
        }
        file_put_contents(PK3_FOLDER . DIRECTORY_SEPARATOR . "RVERSION", "Build number " . $new_build_number . ", built: " . date("F j, Y, g:i a T", $start_time));
        
        if (get_setting("PROJECT_FORMAT") == "WAD") {
            $this->set_status("Compiling WAD");
            $this->create_wad();
        } else {
            $this->set_status("Waiting on the ZIP process (this one always takes a minute or so)");
            $this->create_pk3();
        }
        //Unmutex
        @unlink(LOCK_FILE_COMPILE);
        $this->set_status("Complete");
        Logger::pg("Download generating lock released");
        $seconds = time() - $start_time;
        Logger::pg("Project generated in " . $seconds . " seconds, build number " . $new_build_number);
        Logger::record_pk3_generation($start_time, $seconds);
        $numberer->set_new_build_number($new_build_number);
        
        return true;
    }

    function write_guide_dialogue() {
        Logger::pg("Processing hub WAD");
        if (!get_setting('GUIDE_ENABLED')) {
            Logger::pg("Guide is not enabled, not touching anything");
            return;
        }

        $hub_map_location = PK3_FOLDER . DIRECTORY_SEPARATOR . "maps" . DIRECTORY_SEPARATOR . get_setting("HUB_MAP_NAME") . ".wad";
        $wad_in = new Wad_Handler($hub_map_location);
        $wad_out = new Wad_Handler();

        if (!$wad_in->count_lumps()) {
            Logger::pg("There is no hub wad in " . get_setting("HUB_MAP_NAME") . ".wad" . " - skipping");
            return;
        }
        
        //Go through our uploaded WAD and copy all the lumps. Inject our DIALOGUE after BEHAVIOR, or append it to existing one
        $has_existing_dialogue = $wad_in->get_lump("DIALOGUE");
        foreach ($wad_in->lumps as $lump) {
            Logger::pg("Got lump " . $lump['name'] . " from hub WAD");
            if ($lump['name'] == 'DIALOGUE' && $has_existing_dialogue) {
                Logger::pg("Appending generated DIALOGUE to existing lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $lump['data'] .= PHP_EOL . $guide_writer->write($has_existing_dialogue);
            }
            $wad_out->add_lump($lump);
            if ($lump['name'] == 'BEHAVIOR' && !$has_existing_dialogue) {
                Logger::pg("Inserting generated DIALOGUE lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $dialogue_lump = ['name' => 'DIALOGUE', 'data' => $guide_writer->write($has_existing_dialogue)];
                $wad_out->add_lump($dialogue_lump);
            }            
        }
        Logger::pg("Writing new hub WAD");
        $bytes_written = $wad_out->write_wad($hub_map_location);
        Logger::pg("Wrote " . $bytes_written . " bytes to new hub WAD");
    }
    
    function generate_map_wads($catalog_handler) {
        Logger::pg("--- IMPORTING MAPS AND RESOURCES FROM UPLOADED WADS ---");
        $catalog = $catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;
        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Translating uploaded WADs into maps... " . $map_index . "/" . $total_maps);
            Logger::pg(PHP_EOL . "📦 " . $map_data['lumpname'] . ": Reading source WAD (" . $map_data['map_name'] . ") 📦", $map_data['map_number']);
            
            $map_file_name = get_source_wad_file_name($map_data['map_number']);
            $source_wad = UPLOADS_FOLDER . $map_file_name;
            $wad_handler = new Wad_Handler($source_wad);
            if (!$wad_handler->count_lumps()) {
                Logger::pg("❌ " . $map_file_name . " does not exist in uploads folder, skipping it", $map_data['map_number'], true);
                continue;
            }

            Logger::pg($wad_handler->wad_info(), $map_data['map_number']);
            
            $this->warn_unsupported_lumps($map_data, $wad_handler);
            $this->import_map($map_data, $wad_handler);
            $this->import_sndinfo_and_sounds($map_data, $wad_handler);
            $this->import_between_markers($map_data, $wad_handler, ['P_START', 'PP_START', 'PPSTART'], ['P_END', 'PP_END', 'PPEND'], 'patches', 'patch');
            $this->import_between_markers($map_data, $wad_handler, ['TX_START'], ['TX_END'], 'textures', 'texture');
            $this->import_between_markers($map_data, $wad_handler, ['VX_START'], ['VX_END'], 'voxels', 'voxel');
            $this->import_between_markers($map_data, $wad_handler, ['FF_START', 'F_START'], ['FF_END', 'F_END'], 'flats', 'flat');
            $this->import_between_markers($map_data, $wad_handler, ['S_START', 'SS_START'], ['S_END', 'SS_END'], 'sprites', 'sprite');
            $this->import_between_markers($map_data, $wad_handler, ['MS_START'], ['MS_END'], 'music', 'music');
            $this->import_between_markers($map_data, $wad_handler, ['MD_START'], ['MD_END'], 'models', 'models', '.md3');
            $this->import_between_markers($map_data, $wad_handler, ['MO_START'], ['MO_END'], 'models', 'models', '.obj');
            $this->import_between_markers($map_data, $wad_handler, ['MT_START'], ['MT_END'], 'models', 'models', '.png');
            $this=>import_modeldefs($map_data, $wad_handler, $imported_md3s, $imported_objs);
            $this->import_lumps_directly($map_data, $wad_handler, ['TEXTURES', 'GLDEFS', 'ANIMDEFS', 'LOCKDEFS', 'SNDSEQ', 'README', 'MANUAL', 'VOXELDEF', 'TEXTCOLO', 'SPWNDATA']);
            $this->import_music($map_data, $wad_handler);
            $this->import_scripts($map_data, $wad_handler);
            $this->import_mapinfo($map_data, $wad_handler);
        }
    }
    
    function warn_unsupported_lumps($map_data, $wad_handler) {
        $map_number = $map_data['map_number'];
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump['name'], ['TEXTURE1', 'TEXTURE2'])) {
                Logger::pg("❌ TEXTUREx lumps are unsupported - convert to TEXTURES with SLADE3", $map_number, true);
            }
            if (in_array($lump['name'], ['ANIMATED', 'SWITCHES', 'SWANTBLS'])) {
                Logger::pg("❌ " . $lump['name'] . " lumps are unsupported - convert to ANIMDEFS with SLADE3", $map_number, true);
            }
            if (in_array($lump['name'], ['PLAYPAL', 'COLORMAP'])) {
                Logger::pg("❌ " . $lump['name'] . " lumps are unsupported", $map_number, true);
            }
            if (in_array($lump['name'], ['C_START', 'C_END'])) {
                Logger::pg("❌ " . $lump['name'] . ": Colormaps are unsupported", $map_number, true);
            }
        }
    }
    
    function import_sndinfo_and_sounds($map_data, $wad_handler) {
        $sndinfo_lines_to_import = [];
        $sound_lumps_to_extract = [];
        $sndinfo_lumps = $wad_handler->get_lumps("SNDINFO");
        if ($sndinfo_lumps) {
            // Add comment showing map identification
            $sndinfo_lines_to_import[] = ("// " . $map_data['lumpname'] . ": " . $map_data['map_name']);
            
            // Get all lines we want to include
            foreach ($sndinfo_lumps as $sndinfo_lump) {
                Logger::pg("🔊 Found SNDINFO, attempting to parse it", $map_data['map_number']);
                $sndinfo_handler = new Sndinfo_Handler($sndinfo_lump["data"]);
                $sndinfo_result = $sndinfo_handler->parse();
                //sndinfo lumps come back with [0] the lines to add to the combined sndinfo, [1] the sound lump names it needs
                $sndinfo_lines_to_import = array_merge($sndinfo_lines_to_import, $sndinfo_result[0]);
                $sound_lumps_to_extract = array_merge($sound_lumps_to_extract, $sndinfo_result[1]);
            }
            
            // We have the sound lumps we want to extract - let's look through and do that
            foreach ($wad_handler->lumps as $lump) {
                if (in_array($lump['name'], $sound_lumps_to_extract)) {
                    Logger::pg("🔈 Found " . $lump['name'] . " mentioned in SNDINFO - assuming it's a sound", $map_data['map_number']);
                    if (!$this->add_lump_to_global_list($lump['name'], md5($lump['data']), $map_data['map_number'])) {
                        continue;
                    }
                    //This is a lump mentioned in SNDINFO! Copy it into the sounds folder
                    $sound_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . "sounds" . DIRECTORY_SEPARATOR . $map_data['lumpname'];
                    @mkdir($sound_folder, 0755, true);
                    $sound_path = $sound_folder . DIRECTORY_SEPARATOR . $lump["name"];
                    file_put_contents($sound_path, $lump["data"]);
                    Logger::pg("Wrote " . strlen($lump["data"]) . " bytes to " . $sound_path, $map_data['map_number']);
                    continue;
                }
            }            
            
            //Finally, write our compiled SNDINFO
            if ($sndinfo_lines_to_import) {
                $sndinfo_filename = PK3_FOLDER . "SNDINFO." . $map_data['map_number'];
                @unlink($sndinfo_filename);
                file_put_contents($sndinfo_filename, implode(PHP_EOL, $sndinfo_lines_to_import));
                Logger::pg("🔊 Wrote " . $sndinfo_filename, $map_data['map_number']);
            }
        }
    }
    
    function import_map($map_data, $wad_handler) {
        $in_map = false;
        $map_lumps = [];
        
        foreach ($wad_handler->lumps as $lump) {
            //If we're in a map and this lump is not map data, we are no longer in a map!
            if ($lump['type'] != 'mapdata' && $in_map) {
                //TODO Allow multiple maps per file?
                Logger::pg("Finished reading map lumps", $map_data['map_number']);
                break;
            }
            if (($lump['type'] == 'mapmarker' && !$in_map) || ($lump['type'] == 'mapdata' && $in_map)) {
                $in_map = true;
                $map_lumps[] = $lump;
                Logger::pg("Read map lump " . $lump['name'] . " with size " . strlen($lump['data']), $map_data['map_number']);
            }
        }
        
        if (count($map_lumps) <= 1) {
            Logger::pg("❌ No map lumps read after map marker, possible malformed WAD. Map will not be included", $map_data['map_number'], true);
            return;
        }
        
        //Construct a new WAD using only the map lumps
        $target_wad = PK3_FOLDER . "maps" . DIRECTORY_SEPARATOR . $map_data['lumpname'] . ".WAD";
        Logger::pg("🗺 Writing map WAD as " . $target_wad, $map_data['map_number']);
        $wad_writer = new Wad_Handler();
        foreach ($map_lumps as $lump) {
            $wad_writer->add_lump($lump);
        }
        @unlink($target_wad);
        $bytes_written = $wad_writer->write_wad($target_wad);
        Logger::pg("Wrote " . $bytes_written . " bytes to " . $target_wad, $map_data['map_number']);
    }
    
    function import_between_markers($map_data, $wad_handler, $start_names, $stop_names, $folder_name, $type_to_display, $filename_extension = NULL) {
        $in_zone = false;
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump['name'], $start_names)) {
                $in_zone = true;
                Logger::pg("🔽 Found starting marker for " . $folder_name, $map_data['map_number']);
                continue;
            }
            if ($in_zone && in_array($lump['name'], $stop_names)) {
                Logger::pg("🔼 Found ending marker for " . $folder_name, $map_data['map_number']);
                $in_zone = false;
                continue;
            }
            if ($in_zone && $lump['data']) {
                //If we're between start and stop names, import these lumps
                if (!$this->add_lump_to_global_list($lump['name'], md5($lump['data']), $map_data['map_number'])) {
                    continue;
                }
                $lump_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . $folder_name . DIRECTORY_SEPARATOR . $map_data['lumpname'] . DIRECTORY_SEPARATOR;
                @mkdir($lump_folder, 0755, true);
                $output_file = $lump_folder . DIRECTORY_SEPARATOR . $lump['name'] . $filename_extension;
                file_put_contents($output_file, $lump['data']);
                Logger::pg("Wrote " . $type_to_display . " " . $output_file, $map_data['map_number']);
            }
        }
        //If we're still in the zone at the end, something went wrong
        if ($in_zone) {
            Logger::pg("❌ Didn't find an end lump for " . $folder_name . " - expected one of: " . implode(", ", $stop_names) . ". This might have caused further problems", $map_data['map_number'], true);
        }
    }
    
    function import_lumps_directly($map_data, $wad_handler, $allowed_lump_names) {
        
        $included_lump_counts = [];
        
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump['name'], $allowed_lump_names)) {
                
                //Special case - reject LOCKDEFS if it tries to clear locks
                if ($lump['name'] == 'LOCKDEFS' && strpos(strtolower($lump['data']), 'clearlocks') !== false) {
                    Logger::pg("❌ Found " . $lump['name'] . " lump but refusing it as it performs CLEARLOCKS!", $map_data['map_number'], true);
                    continue;
                }
                
                Logger::pg("💾 Including " . $lump["name"] . " lump", $map_data['map_number']);
                if (!isset($included_lump_counts[$lump["name"]])) {
                    $included_lump_counts[$lump["name"]] = 1;
                }
                else {
                    $included_lump_counts[$lump["name"]]++;
                }
                
                @mkdir(PK3_FOLDER, 0755, true);
                $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . $lump['name'] . "." . $map_data['map_number'] . "-" . $included_lump_counts[$lump['name']];
                file_put_contents($data_path, $lump['data']);
                Logger::pg("Wrote " . strlen($lump["data"]) . " bytes to " . $data_path, $map_data['map_number']);
            }
        }
    }

    function import_modeldefs($map_data, $wad_handler) {

        $number_of_modeldefs = 0;

        //import MODELDEF files
        //there could be multiple files with one definition in each, or one file with multiple definitions
        //this should handle both cases -- or even a mixture of cases -- fine
        foreach ($wad_handler->lumps as $lump) {
            if ($lump['name'] == 'MODELDEF') {
                $number_of_modeldefs++;
                
                Logger::pg("💾 Including " . $lump["name"] . " lump " . $number_of_modeldefs, $map_data['map_number']);     

                //MODELDEF files must go in the root directory of the pk3           
                $data_path = PK3_FOLDER . DIRECTORY_SEPARATOR . $lump['name'] . "." . $map_data['map_number'] . "-" . $number_of_modeldefs;

                $modeldef_lines = split(PHP_EOL, $lump['data']);

                $modeldef_data = "";

                foreach ($modeldef_lines as $line) {

                    if(preg_match('/MODEL\s+0/i', $line)){
                        //For each model definition in a legal model definition there will always be one file starting "Model 0"
                        //We need to add one Path definition line to each model definition, matching the path to models for the map being inported
                        //So it makes sense to add it immediately prior to the "Model 0" line
                        $modeldef_data .= 'Path "models' . DIRECTORY_SEPARATOR . $map_data['lumpname'] . DIRECTORY_SEPARATOR . '"' . PHP_EOL
                    } 

                    //Since RAMPART adds its own Path definition line, we should only include the file line if it
                    //isn't one already set by the mapper
                    //note that allowing the mapper to include a Path line which is ignored makes it easier for the
                    //mapper to run their map both locally for testing and as part of the built project
                    if (!preg_match('/PATH/i', $line) {
                        $modeldef_data .= $line . PHP_EOL;
                    } 
                }

                file_put_contents($data_path, $modeldef_data);
                Logger::pg("Wrote " . strlen($modeldef_data) . " bytes to " . $data_path, $map_data['map_number']);
            }
        }
    }
    
    function import_music($map_data, $wad_handler) {
        //If we have more than one MIDI, assume that MAPINFO will handle everything
        $number_of_midis = 0;
        $midis_imported = 0;
        foreach ($wad_handler->lumps as $lump) {
            if ($lump['type'] == 'midi') {
                $number_of_midis++;
            }
        }
        if ($number_of_midis > 1) {
            foreach ($wad_handler->lumps as $lump) {
                if ($lump['type'] == 'midi') {
                    if (!$this->add_lump_to_global_list($lump['name'], md5($lump['data']), $map_data['map_number'])) {
                        continue;
                    }
                    $midis_imported++;
                    Logger::pg("🎵 Importing " . $midis_imported . " of " . $number_of_midis . " MIDIs found in WAD: " . $lump['name'], $map_data['map_number']);
                    $music_path = PK3_FOLDER . "music/" . $lump['name'] . ".mid";
                    file_put_contents($music_path, $lump['data']);
                }
            }
            return;
        }

        //If not, find our first music then name it according to our lump map - prioritize MIDI first then check others later
        if ($this->music_lump_mapper == null) {
            $this->music_lump_mapper = new Music_Lump_Mapper();
        }
        
        $possible_music_types = ['midi', 'mus', 'module', 'mp3'];
        foreach ($possible_music_types as $looking_for_music_type) {
            foreach ($wad_handler->lumps as $lump) {
                if ($lump['type'] == $looking_for_music_type) {
                    $music_type = $lump['type'];
                    $music_length = strlen($lump['data']);
                    Logger::pg("🎵 Music of type " . $lump['type'] . " found in lump " . $lump['name'] . " with size " . $music_length, $map_data['map_number']);
                    $music_path = PK3_FOLDER . "music/" . $this->music_lump_mapper->get_name_for_music_lump($map_data['lumpname']);
                    file_put_contents($music_path, $lump['data']);
                    Logger::pg("Wrote " . $music_length . " bytes to " . $music_path, $map_data['map_number']);
                    return;
                }
            }
        }
        
        //If we still don't have a music, check for OGGs and FLACs
        foreach ($wad_handler->lumps as $lump) {
            if (in_array($lump['type'], ['flac', 'ogg'])) {
                $music_type = $lump['type'];
                $music_length = strlen($lump['data']);
                Logger::pg("🎵 Music of type " . $lump['type'] . " found in lump " . $lump['name'] . " with size " . $music_length, $map_data['map_number']);
                $music_path = PK3_FOLDER . "music/" . $this->music_lump_mapper->get_name_for_music_lump($map_data['lumpname']);
                file_put_contents($music_path, $lump['data']);
                Logger::pg("Wrote " . $music_length . " bytes to " . $music_path, $map_data['map_number']);
                return;
            }
        }
    }
    
    function import_scripts($map_data, $wad_handler) {
        //Copy DECORATE or ZSCRIPT into files in the scripts folder, and append the name on to the include file in the root
        $lumpnumber = 0;
        foreach ($wad_handler->lumps as $lump) {
            if (in_array(strtoupper($lump['name']), ['DECORATE', 'ZSCRIPT'])) {
                if (strpos($lump['data'], "replaces") !== false) { //Okay, I don't have time to write a proper parser
                    Logger::pg("❌ Found " . $lump['name'] . " lump but refusing it as it performs replacements!", $map_data['map_number'], true);
                    continue;
                }
                Logger::pg("📜 Found " . $lump['name'] . " lump, adding it to our script folder", $map_data['map_number']);
                $lumpnumber++;
                $script_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . strtoupper($lump['name']);
                @mkdir($script_folder, 0755, true);
                $script_file_name = strtoupper($lump['name']) . "-" . $map_data['map_number'] . "-" . $lumpnumber . ".txt";
                $script_file_path = $script_folder . DIRECTORY_SEPARATOR . $script_file_name;
                
                //If this is a ZSCRIPT file and it begins with a version declaration, we have to strip that out
                if ($lump['name'] == 'ZSCRIPT' && substr($lump['data'], 0, 7) == "version") {
                    Logger::pg("Taking version declaration out of ZSCRIPT lump");
                    $first_newline_position = strpos($lump['data'], PHP_EOL);
                    $lump['data'] = substr($lump['data'], $first_newline_position);
                }
                
                file_put_contents($script_file_path, $lump['data']);
                Logger::pg("Wrote " . strlen($lump['data']) . " bytes to " . $script_file_path, $map_data['map_number']);
                
                //If this is our first ZSCRIPT inclusion, we need to add the version declaration
                $script_include_file_path = PK3_FOLDER . strtoupper($lump['name']) . ".custom";
                if (strtoupper($lump['name']) == "ZSCRIPT" && !file_exists($script_include_file_path)) {
                    file_put_contents($script_include_file_path, "version \"4.7.1\"" . PHP_EOL . PHP_EOL);
                }
                
                file_put_contents($script_include_file_path, "#include \"" . strtoupper($lump['name']) . DIRECTORY_SEPARATOR . $script_file_name . "\"" . PHP_EOL, FILE_APPEND);

            }
        }
    }
    
    function import_mapinfo($map_data, $wad_handler) {
        foreach ($wad_handler->lumps as $lump) {
            if (in_array(strtoupper($lump['name']), ['MAPINFO', 'ZMAPINFO'])) {
                Logger::pg("📜 Found " . $lump['name'] . " lump, parsing it", $map_data['map_number']);
                $mapinfo_handler = new Mapinfo_Handler($lump['data']);
                $mapinfo_properties = $mapinfo_handler->parse();
                if (isset($mapinfo_properties['error'])) {
                    Logger::pg("❌ " . $mapinfo_properties['error'], $map_data['map_number'], true);
                    continue;
                }
                // Doomednums will all be added at the end - put them into a global array as we encounter them
                if (isset($mapinfo_properties['doomednums'])) {
                    foreach($mapinfo_properties['doomednums'] as $dnum => $classname) {
                        if (isset($this->wad_variables['custom_defined_doomednums'][$dnum])) {
                            Logger::pg("❌ DoomedNum conflict: " . $dnum . " refers to " . $classname . " and " . $this->wad_variables['custom_defined_doomednums'][$dnum], $map_data['map_number'], true);
                            continue;
                        }
                        $this->wad_variables['custom_defined_doomednums'][$dnum] = $classname;
                        Logger::pg("DoomEdNum " . $dnum . " = " . $classname, $map_data['map_number']);
                    }
                }
                if (isset($mapinfo_properties['spawnnums'])) {
                    foreach($mapinfo_properties['spawnnums'] as $dnum => $classname) {
                        if (isset($this->wad_variables['custom_defined_spawnnums'][$dnum])) {
                            Logger::pg("❌ SpawnNum conflict: " . $dnum . " refers to " . $classname . " and " . $this->wad_variables['custom_defined_spawnnums'][$dnum], $map_data['map_number'], true);
                            continue;
                        }
                        $this->wad_variables['custom_defined_spawnnums'][$dnum] = $classname;
                        Logger::pg("SpawnNum " . $dnum . " = " . $classname, $map_data['map_number']);
                    }
                }
                // For any other index-value pair, as long as it's a string, check it against the allowed properties and add it if it's OK
                foreach ($mapinfo_properties as $index => $value) {
                    if (!is_string($value)) {
                        continue;
                    }
                    Logger::pg("\t" . $index . ": " . $value, $map_data['map_number']);
                    if(in_array($index, ALLOWED_MAPINFO_PROPERTIES)) {
                        $this->write_map_variable($map_data['map_number'], $index, $value);
                        Logger::pg("Found custom allowable MAPINFO property " . $index . " - adding value " . $value . " to custom properties array", $map_data['map_number']);
                    }
                }

                //Check for SKY1 and SKY2
                for ($i = 1; $i <= 2; $i++) {
                    if (isset($mapinfo_properties['sky' . $i])) {
                        $skylumpname = $mapinfo_properties['sky' . $i];
                        Logger::pg("⛅ SKY" . $i . " property found in MAPINFO: " . $skylumpname, $map_data['map_number']);
                        //If the sky has been provided in the WAD, copy it in for this map!
                        if ($skylump = $wad_handler->get_lump($skylumpname)) {
                            Logger::pg("⛅ Found lump " . $skylumpname . " pointed to by SKY" . $i . ", including it", $map_data['map_number']);
                            $skyfile = $this->write_sky_to_pk3($map_data['map_number'], $i, $skylump['data']);
                            $this->write_map_variable($map_data['map_number'], 'sky' . $i, $skyfile); // Set the sky file name decided on by the sky writer
                        } else {
                            Logger::pg("No lump " . $skylumpname . " pointed to by SKY" . $i . ", trusting it's already included", $map_data['map_number']);
                            $this->write_map_variable($map_data['map_number'], 'sky' . $i, $skylumpname); // Just keep the existing name
                        }
                    }
                }
            }
            //If we have an entry that matches the default sky lump, use that as sky
            if (!empty(get_setting("DEFAULT_SKY_LUMP")) && (strtoupper($lump['name']) == strtoupper(get_setting("DEFAULT_SKY_LUMP")))) {
                Logger::pg("⛅ " . get_setting("DEFAULT_SKY_LUMP") . " default sky lump found with size " . strlen($lump['data']) . " - including it", $map_data['map_number']);
                $skyfile = $this->write_sky_to_pk3($map_data['map_number'], 1, $lump['data']);
                if (!isset($this->map_additional_mapinfo[$map_data['map_number']])) { $this->map_additional_mapinfo[$map_data['map_number']] = []; }
                $this->map_additional_mapinfo[$map_data['map_number']]['sky1'] = $skyfile;
                continue;
            }
        }
    }
    
    function write_map_variable($map_number, $key, $value) {
        if (!isset($this->map_additional_mapinfo[$map_number])) { $this->map_additional_mapinfo[$map_number] = []; }
        $this->map_additional_mapinfo[$map_number][$key] = $value;
    }
    
    function generate_marquees($catalog_handler) {
        Logger::pg("Generating marquee textures");
        $marquee_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . "textures" . DIRECTORY_SEPARATOR . "marquee";
        @mkdir($marquee_folder, 0755, true);
        
        $catalog = $catalog_handler->get_catalog();
        foreach ($catalog as $map_data) {
            
            $map_name = $map_data['map_name'];
            foreach([0, 1] as $is_blue) {
                $params = urlencode(json_encode(["name" => $map_name, "active" => !$is_blue]));
                $image_bytes = file_get_contents("http://trackthet.com/services/marquee/generate_marquees.php?params=" . $params);
                $marquee_prefix = $is_blue ? "MARX" : "MARQ";
                $marquee_file = $marquee_folder . DIRECTORY_SEPARATOR . $marquee_prefix . $map_data['map_number'] . ".png";
                file_put_contents($marquee_file, $image_bytes);
                Logger::pg("Wrote " . $marquee_file);
            }
        }
    }

    /**
     * Writes the MAPINFO, LANGUAGE and other data lumps using the map properties and whether we've found music, skies, etc
     */
    function generate_info($catalog_handler) {
        
        Logger::pg("--- GENERATING INFO LUMPS ---");
        
        $catalog = $catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;

        //For every map in the catalog, write a MAPINFO entry and LANGUAGE lump.
        $mapinfo = "";
        $language = "[enu default]" . PHP_EOL . PHP_EOL;
        $rampdata = [];

        $allow_jump = get_setting("ALLOW_GAMEPLAY_JUMP");
        $write_mapinfo = get_setting("PROJECT_WRITE_MAPINFO");

        foreach ($catalog_handler->get_catalog() as $map_data) {
            $map_index++;
            $this->set_status("Generating MAPINFO and other descriptors like that... " . $map_index . "/" . $total_maps);

            //Check flags set by user
            $map_allows_jump = 0;
            if (
                (
                    ($allow_jump == 'always') ||
                    ((isset($map_data['jumpcrouch']) && $map_data['jumpcrouch'] == 1))
                )
                && ($allow_jump != 'never')
            ) {
                $map_allows_jump = 1;
            }
            $map_is_wip = 0;
            if (isset($map_data['wip']) && $map_data['wip'] == 1) {
                $map_is_wip = 1;
            }

            $language .= $map_data['lumpname'] . "NAME = \"" . $map_data['map_name'] . "\";" . PHP_EOL;
            $language .= $map_data['lumpname'] . "AUTH = \"" . $map_data['author'] . "\";" . PHP_EOL;
            $language .= $map_data['lumpname'] . "MUSC = \"" . (isset($map['music_credit']) ? $map['music_credit'] : '') . "\";" . PHP_EOL;
            $language .= PHP_EOL;

            $rampdata[$map_data['map_number']] = implode(",", [$map_allows_jump, $map_is_wip, $map_data['map_number'], $map_data['lumpname']]);

            if (!$write_mapinfo) {
                continue;
            }
            
            //Header
            $mapinfo .= "map " . $map_data['lumpname'] . " lookup " . $map_data['lumpname'] . "NAME" . PHP_EOL;
            
            //The basics - include the name, author, and point everything to go back to MAP01
            $mapinfo .= "{" . PHP_EOL;
            $mapinfo .= "author = \"$" . $map_data['lumpname'] . "AUTH\"" . PHP_EOL;
            $mapinfo .= "levelnum = " . $map_data['map_number'] . PHP_EOL;
            $mapinfo .= "cluster = 1" . PHP_EOL;
            
            //Include any allowed custom properties from original upload
            Logger::pg("Checking for custom properties for map number " .$map_data['map_number'], $map_data['map_number']);
            if (isset($this->map_additional_mapinfo[$map_data['map_number']])) {
                $custom_properties = $this->map_additional_mapinfo[$map_data['map_number']];
                foreach ($custom_properties as $index => $property) {
                    if (in_array($index, ['music'])) {
                        continue; //We'll handle music specifically later
                    }
                    Logger::pg("Including custom property " . $index . " as " . $property, $map_data['map_number']);
                    if ($property == '_SET_') { //Used to flag properties that take no value
                        $mapinfo .= "\t" . $index . PHP_EOL;
                    } else {
                        $mapinfo .= "\t" . $index . " = " . $property . PHP_EOL;
                    }
                }
            }
            
            //Skies. If we haven't got a specific one (which will have been added by the custom properties above),
            //check for a sky written to the folder, then fall back to default
            if (!isset($this->map_additional_mapinfo[$map_data['map_number']]['sky1'])) {
                Logger::pg("No sky1 set, falling back to RSKY1 for map " . $map_data['map_number'], $map_data['map_number']);
                $mapinfo .= "\t" . "sky1 = RSKY1" . PHP_EOL;
            }

            //Use this map's music if we've already parsed it out. If not, try the music in the custom properties. Then fall back to our default
            if ($this->music_lump_mapper == null) {
                $this->music_lump_mapper = new Music_Lump_Mapper();
            }
            
            $expected_music_lump = $this->music_lump_mapper->get_name_for_music_lump($map_data['lumpname']);
            $expected_music_file = PK3_FOLDER . "music" . DIRECTORY_SEPARATOR . $expected_music_lump;
        
            if (file_exists($expected_music_file)) {
                Logger::pg("Using music lump " . $expected_music_lump . " for map " . $map_data['map_number'], $map_data['map_number']);
                $mapinfo .= "\t" . "music = " . $expected_music_lump . PHP_EOL;
            } else if (isset($this->map_additional_mapinfo[$map_data['map_number']]['music'])) {
                $music_lump = $this->map_additional_mapinfo[$map_data['map_number']]['music'];
                Logger::pg("Using specific music lump " . $music_lump . " for map " . $map_data['map_number'], $map_data['map_number']);
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

            //Finally, include properties specified by map data. If there is nothing in MAPINFO, default to setting the next level to the hub
            $mapinfo .= (isset($map_data['mapinfo']) ? $map_data['mapinfo'] : 'next = ' . get_setting("HUB_MAP_NAME")) . PHP_EOL;

            $mapinfo .= "}" . PHP_EOL;
            $mapinfo .= PHP_EOL;
        }
        
        // If custom doomednums were found during WAD creation, add them to global MAPINFO now
        if ($this->wad_variables['custom_defined_doomednums']) {
            $mapinfo .= "doomednums {" . PHP_EOL;
            foreach ($this->wad_variables['custom_defined_doomednums'] as $dnum => $classname) {
                $mapinfo .= "    " . $dnum . " = " . "\"" . $classname . "\"" . PHP_EOL;
            }
            $mapinfo .= "}" . PHP_EOL;
        }
        if ($this->wad_variables['custom_defined_spawnnums']) {
            $mapinfo .= "spawnnums {" . PHP_EOL;
            foreach ($this->wad_variables['custom_defined_spawnnums'] as $dnum => $classname) {
                $mapinfo .= "    " . $dnum . " = " . "\"" . $classname . "\"" . PHP_EOL;
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
        
        ksort($rampdata);
        $rampdata_filename = PK3_FOLDER . "RAMPDATA.rampart";
        @unlink($rampdata_filename);
        file_put_contents($rampdata_filename, implode(PHP_EOL, $rampdata));
        Logger::pg("Wrote " . $rampdata_filename);
    }

    function write_sky_to_pk3($mapnum, $skynum, $sky_bytes) {
        $skyfile = "MSKY";
        if ($skynum == 2) {
            $skyfile = "MSKZ";
        }
        $skyfile .= $mapnum;
        $folder = PK3_FOLDER . "textures/MAP" . $mapnum;
        @mkdir($folder, 0755, true);
        $sky_file_path = $folder . "/" . $skyfile;
        file_put_contents($sky_file_path, $sky_bytes);
        Logger::pg("Wrote " . strlen($sky_bytes) . " bytes to " . $sky_file_path);
        return $skyfile;
    }
    
    function create_wad() {
        Logger::pg("--- WRITING WAD ---");
        $wad_out = new Wad_Handler();
        
        //Include contents of any resource WADs
        if (file_exists(RESOURCE_WAD_FOLDER)) {
            $resource_wads = scandir(RESOURCE_WAD_FOLDER);
            foreach($resource_wads as $resource_wad) {
                if (!is_file(RESOURCE_WAD_FOLDER . $resource_wad) || substr($resource_wad, 0, 1) == ".") {
                    continue;
                }
                $wad_in = new Wad_Handler(RESOURCE_WAD_FOLDER . $resource_wad);
                foreach($wad_in->lumps as $lump) {
                    Logger::pg("Including resource WAD " . $resource_wad . "->" . $lump['name']);
                    $wad_out->add_lump($lump);
                }
            }
        }
        
        // MAPS
        
        $files = scandir(MAPS_FOLDER);
        foreach($files as $file) {
            if (!is_file(MAPS_FOLDER . $file) || substr($file, 0, 1) == ".") {
                continue;
            }
            $wad_in = new Wad_Handler(MAPS_FOLDER . $file);
            foreach($wad_in->lumps as $lump) {
                Logger::pg("Including " . $file . "->" . $lump['name']);
                //If this is our map marker, the lump name in the WAD must be the file name!
                if ($lump['type'] == "mapmarker") {
                    $lump['name'] = substr($file, 0, strpos($file, "."));
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
            if (!is_file(PK3_FOLDER . $file) || substr($file, 0, 1) == ".") {
                continue;
            }
            Logger::pg("Including from root folder: " . $file);
            $lump_name = $this->get_lump_name_from_path($file);
            $lump = ['name' => $lump_name, 'type' => '', 'data' => file_get_contents(PK3_FOLDER . $file)];
            $wad_out->add_lump($lump);
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
                $wad_out->add_lump(['name' => $start_marker, 'type' => '', 'data' => '']);
                $wrote_start_marker = true;
            }
            $lump_name = $this->get_lump_name_from_path($file->getRealPath());
            Logger::pg("Including lump for " . $folder . ": " . $lump_name);
            $filePath = $file->getRealPath();
            $lump = ['name' => $lump_name, 'type' => '', 'data' => file_get_contents($filePath)];
            $wad_out->add_lump($lump);
        }
        
        if ($wrote_start_marker && $end_marker) {
            $wad_out->add_lump(['name' => $end_marker, 'type' => '', 'data' => '']);
        }
    }
    
    function get_lump_name_from_path($path) {
        $end_char = strpos(basename($path), ".") ? strpos(basename($path), ".") : 8;
        return strtoupper(substr(substr(basename($path), 0, $end_char), 0, 8));
    }

    function create_pk3() {
        if (!empty($GLOBALS["ZIP_SCRIPT"])) {
            Logger::pg("--- ASKING EXTERNAL SCRIPT TO ZIP PK3 ---");
            exec($GLOBALS["ZIP_SCRIPT"]);
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
          new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
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

    function clean() {
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
        mkdir(PK3_FOLDER);
        $path = realpath(PK3_FOLDER);
        foreach (PK3_REQUIRED_FOLDERS as $folder) {
            mkdir($path . DIRECTORY_SEPARATOR . $folder);
        }
    }

    function deleteAll($str) {      
        if (is_file($str)) {
            return unlink($str);
        }
        elseif (is_dir($str)) {
            $scan = glob(rtrim($str, '/').'/*');
            foreach($scan as $index=>$path) {
                $this->deleteAll($path);
            }
            return @rmdir($str);
        }
    }

    function set_status($string) {
        file_put_contents(STATUS_FILE, $string);
    }
}

