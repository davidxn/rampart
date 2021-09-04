<?php

require_once("_constants.php");
require_once("_functions.php");
require_once("scripts/wad_handler.php");
require_once("scripts/mapinfo_handler.php");
require_once("scripts/guide_writer.php");
require_once("scripts/catalog_handler.php");
require_once("scripts/build_numberer.php");

class Project_Compiler {

    public $map_additional_mapinfo = [];

    function compile_project() {

        //Begin!
        $start_time = time();
        Logger::clear_pk3_log();
        $this->set_status("Initializing");
        file_put_contents(LOCK_FILE_COMPILE, ":)");
        @mkdir(get_setting("PROJECT_OUTPUT_FOLDER"), 0777, true);

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
        if (!GUIDE_ENABLED) {
            Logger::pg("Guide is not enabled, not touching anything");
            return;
        }

        $hub_map_location = PK3_FOLDER . DIRECTORY_SEPARATOR . get_setting("HUB_MAP_FILE");
        $wad_in = new Wad_Handler($hub_map_location);
        $wad_out = new Wad_Handler();

        if (!$wad_in->count_lumps()) {
            Logger::pg("There is no hub wad in " . get_setting("HUB_MAP_FILE") . " - skipping");
            return;
        }
        
        //Go through our uploaded WAD and copy all the lumps. Inject our DIALOGUE after BEHAVIOR, or append it to existing one
        $has_existing_dialogue = $wad_in->get_lump("DIALOGUE");
        foreach ($wad_in->lumps as $lump) {
            Logger::pg("Got lump " . $lump['name'] . " from hub WAD");
            if ($lump['name'] == 'DIALOGUE' && $has_existing_dialogue) {
                Logger::pg("Appending generated DIALOGUE to existing lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $lump['data'] .= PHP_EOL . $guide_writer->write();
            }
            $wad_out->add_lump($lump);
            if ($lump['name'] == 'BEHAVIOR' && !$has_existing_dialogue) {
                Logger::pg("Inserting generated DIALOGUE lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $dialogue_lump = ['name' => 'DIALOGUE', 'data' => $guide_writer->write()];
                $wad_out->add_lump($dialogue_lump);
            }            
        }
        Logger::pg("Writing new hub WAD");
        $bytes_written = $wad_out->write_wad($hub_map_location);
        Logger::pg("Wrote " . $bytes_written . " bytes to new hub WAD");
    }
    
    /**
     * Writes the MAPINFO and LANGUAGE lumps using the map properties and whether we've found music, skies, etc
     */
    function generate_info($catalog_handler) {
        
        Logger::pg("--- GENERATING INFO LUMPS ---");
        
        $catalog = $catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;

        //For every map in the catalog, write a MAPINFO entry and LANGUAGE lump.
        $mapinfo = "";
        $language = "[enu default]" . PHP_EOL . PHP_EOL;

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
            $language .= $map_data['lumpname'] . "SP_JUMP = \"" . $map_allows_jump . "\";" . PHP_EOL;
            $language .= $map_data['lumpname'] . "SP_WIP = \"" . $map_is_wip . "\";" . PHP_EOL;
            $language .= PHP_EOL;

            if (!$write_mapinfo) {
                continue;
            }
            
            //Header
            $mapinfo .= "map " . $map_data['lumpname'] . " \"" . $map_data['map_name'] . "\"" . PHP_EOL;
            
            //The basics - include the name, author, and point everything to go back to MAP01
            $mapinfo .= "{" . PHP_EOL;
            $mapinfo .= "author = \"" . $map_data['author'] . "\"" . PHP_EOL;
            $mapinfo .= "levelnum = " . $map_data['map_number'] . PHP_EOL;
            
            //Include any allowed custom properties from original upload
            Logger::pg("Checking for custom properties for map number " .$map_data['map_number']);
            if (isset($this->map_additional_mapinfo[$map_data['map_number']])) {
                $custom_properties = $this->map_additional_mapinfo[$map_data['map_number']];
                foreach ($custom_properties as $index => $property) {
                    if (in_array($index, ['music'])) {
                        continue; //We'll handle music specifically later
                    }
                    Logger::pg("Including custom property " . $index . " as " . $property);
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
                Logger::pg("No sky1 set, falling back to RSKY1 for map " . $map_data['map_number']);
                $mapinfo .= "\t" . "sky1 = RSKY1" . PHP_EOL;
            }

            //Use this map's music if we've already parsed it out. If not, try the music in the custom properties. Then fall back to D_RUNNIN
            if (file_exists(PK3_FOLDER . "music/" . "MUS" . $map_data['map_number'])) {
                $mapinfo .= "\t" . "music = MUS" . $map_data['map_number'] . PHP_EOL;
            } else if (isset($this->map_additional_mapinfo[$map_data['map_number']]['music'])) {
                $music_lump = $this->map_additional_mapinfo[$map_data['map_number']]['music'];
                Logger::pg("Using specific music lump " . $music_lump . " for map " . $map_data['map_number']);
                $mapinfo .= "\t" . "music = " . $music_lump . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "music = D_RUNNIN" . PHP_EOL;
            }

            if ($map_allows_jump) {
                $mapinfo .= "\t" . "AllowJump" . PHP_EOL;
                $mapinfo .= "\t" . "AllowCrouch" . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "NoJump" . PHP_EOL;
                $mapinfo .= "\t" . "NoCrouch" . PHP_EOL;
            }

            //Finally, include properties specified by map data
            $mapinfo .= $map_data['mapinfo'] . PHP_EOL;

            $mapinfo .= "}" . PHP_EOL;
            $mapinfo .= PHP_EOL;
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
    }

    function generate_map_wads($catalog_handler) {
        
        Logger::pg("--- GENERATING MAPS ---");
        
        $catalog = $catalog_handler->get_catalog();
        $total_maps = count($catalog);
        $map_index = 0;
        foreach ($catalog as $map_data) {
            $map_index++;
            $this->set_status("Translating uploaded WADs into maps... " . $map_index . "/" . $total_maps);
            $lumpnumber = 0;
            $map_file_name = get_source_wad_file_name($map_data['map_number']);
            Logger::pg(PHP_EOL . "📦 " . $map_data['lumpname'] . ": Reading source WAD (" . $map_data['map_name'] . ") 📦");
            $source_wad = UPLOADS_FOLDER . $map_file_name;
            $target_wad = PK3_FOLDER . "maps/" . $map_data['lumpname'] . ".WAD";
            $wad_handler = new Wad_Handler($source_wad);
            if (!$wad_handler->count_lumps()) {
                Logger::pg("❌ " . $map_file_name . " does not exist in uploads folder, skipping it");
                continue;
            }
            Logger::pg($wad_handler->wad_info());

            $music_bytes = "";
            $map_lumps = [];
            $in_map = false;
            $sky_found = false;
            foreach ($wad_handler->lumps as $lump) {
                if ($lump['type'] != 'mapdata' && $in_map) {
                    if (count($map_lumps) <= 1) {
                        Logger::pg("❌ No lumps read in map, possible malformed WAD");
                    }
                    Logger::pg("Finished reading map");
                    $in_map = false;
                }
                if (($lump['type'] == 'mapmarker' && !$in_map) || ($lump['type'] == 'mapdata' && $in_map)) {
                    $in_map = true;
                    $map_lumps[] = $lump;
                    Logger::pg("Read map lump " . $lump['name'] . " with size " . strlen($lump['data']));
                    continue;
                }
                if (in_array($lump['type'], ['midi', 'ogg', 'mp3', 'mus']) && !$music_bytes) {
                    if (!get_setting("ALLOW_CONTENT_MUSIC")) {
                        Logger::pg("Found " . $lump['type'] . " lump but ignoring it due to project settings");
                    }
                    $music_bytes = $lump['data'];
                    $music_type = $lump['type'];
                    Logger::pg("🎵 Music of type " . $lump['type'] . " found in lump " . $lump['name'] . " with size " . strlen($music_bytes));
                    continue;
                }
                if (in_array(strtoupper($lump['name']), ['MAPINFO', 'ZMAPINFO'])) {
                    Logger::pg("📜 Found " . $lump['name'] . " lump, parsing it");
                    $mapinfo_handler = new Mapinfo_Handler($lump['data']);
                    $mapinfo_properties = $mapinfo_handler->parse();
                    if (isset($mapinfo_properties['error'])) {
                        Logger::pg("❌ " . $mapinfo_properties['error']);
                        continue;
                    }
                    foreach ($mapinfo_properties as $index => $value) {
                        Logger::pg("\t" . $index . ": " . $value);
                        if(in_array($index, ALLOWED_MAPINFO_PROPERTIES)) {
                            if (!isset($this->map_additional_mapinfo[$map_data['map_number']])) { $this->map_additional_mapinfo[$map_data['map_number']] = []; }
                            $this->map_additional_mapinfo[$map_data['map_number']][$index] = $value;
                            Logger::pg("Found custom allowable MAPINFO property " . $index . " - adding value " . $value . " to custom properties array");
                        }
                    }

                    //Check for SKY1 and SKY2
                    for ($i = 1; $i <= 2; $i++) {
                        if (isset($mapinfo_properties['sky' . $i])) {
                            $skylumpname = $mapinfo_properties['sky' . $i];
                            Logger::pg("SKY" . $i . " property found in MAPINFO: " . $skylumpname);
                            $sky_found = true;
                            //If the sky has been provided in the WAD, copy it in for this map!
                            if (($skylump = $wad_handler->get_lump($skylumpname))) {
                                Logger::pg("Found lump " . $skylumpname . " pointed to by SKY" . $i . ", including it");
                                $skyfile = $this->write_sky_to_pk3($map_data['map_number'], $i, $skylump['data']);
                                if (!isset($this->map_additional_mapinfo[$map_data['map_number']])) { $this->map_additional_mapinfo[$map_data['map_number']] = []; }
                                $this->map_additional_mapinfo[$map_data['map_number']]['sky' . $i] = $skyfile;
                            } else {
                                Logger::pg("No lump " . $skylumpname . " pointed to by SKY" . $i . ", trusting it's already included");
                                if (!isset($this->map_additional_mapinfo[$map_data['map_number']])) { $this->map_additional_mapinfo[$map_data['map_number']] = []; }
                                $this->map_additional_mapinfo[$map_data['map_number']]['sky' . $i] = $skylumpname;
                            }
                        }
                    }
                }
                //If we have an entry that matches the default sky lump, use that as sky
                if (!empty(get_setting("DEFAULT_SKY_LUMP")) && (strtoupper($lump['name']) == strtoupper(get_setting("DEFAULT_SKY_LUMP")))) {
                    $sky_bytes = $lump['data'];
                    Logger::pg(get_setting("DEFAULT_SKY_LUMP") . " default sky lump found with size " . strlen($sky_bytes));
                    $skyfile = $this->write_sky_to_pk3($map_data['map_number'], 1, $sky_bytes);
                    if (!isset($this->map_additional_mapinfo[$map_data['map_number']])) { $this->map_additional_mapinfo[$map_data['map_number']] = []; }
                    $this->map_additional_mapinfo[$map_data['map_number']]['sky1'] = $skyfile;
                    continue;
                }
                //Copy DECORATE or ZSCRIPT into files in the scripts folder, and append the name on to the include file in the root
                if (in_array(strtoupper($lump['name']), ['DECORATE', 'ZSCRIPT'])) {
                    if (!get_setting("ALLOW_CONTENT_SCRIPTS")) {
                        Logger::pg("Found " . $lump['name'] . " lump but ignoring it due to project settings");
                    }
                    if (strpos($lump['data'], "replaces") !== false) { //Okay, I don't have time to write a proper parser
                        Logger::pg("Found " . $lump['name'] . " lump but refusing it as it performs replacements!");
                    } else {
                        Logger::pg("Found " . $lump['name'] . " lump, adding it to our script folder");
                        $script_folder = PK3_FOLDER . DIRECTORY_SEPARATOR . strtoupper($lump['name']);
                        @mkdir(PK3_FOLDER);
                        @mkdir($script_folder);
                        $script_file_name = strtoupper($lump['name']) . "-" . $map_data['map_number'] . "-" . $lumpnumber . ".txt";
                        $script_file_path = $script_folder . DIRECTORY_SEPARATOR . $script_file_name;
                        file_put_contents($script_file_path, $lump['data']);
                        Logger::pg("Wrote " . strlen($lump['data']) . " bytes to " . $script_file_path);
                        
                        file_put_contents(PK3_FOLDER . strtoupper($lump['name']) . ".custom", "#include \"" . strtoupper($lump['name']) . DIRECTORY_SEPARATOR . $script_file_name . "\"" . PHP_EOL, FILE_APPEND);
                    }
                }
                $lumpnumber++;
            }
            
            //Write our music if we have it
            if ($music_bytes) {
                $music_path = PK3_FOLDER . "music/" . "MUS" . $map_data['map_number'];
                file_put_contents($music_path, $music_bytes);
                Logger::pg("Wrote " . strlen($music_bytes) . " bytes to " . $music_path);
            }

            //Construct a new WAD using only the map lumps
            $wad_writer = new Wad_Handler();
            foreach ($map_lumps as $lump) {
                $wad_writer->add_lump($lump);
            }
            
            @unlink($target_wad);
            $bytes_written = $wad_writer->write_wad($target_wad);
            Logger::pg("Wrote " . $bytes_written . " bytes to " . $target_wad);
        }
    }
    
    function write_sky_to_pk3($mapnum, $skynum, $sky_bytes) {
        $skyfile = "MSKY";
        if ($skynum == 2) {
            $skyfile = "MSKZ";
        }
        $skyfile .= $mapnum;
        $folder = PK3_FOLDER . "textures/MAP" . $mapnum;
        @mkdir($folder);
        $sky_file_path = $folder . "/" . $skyfile;
        file_put_contents($sky_file_path, $sky_bytes);
        Logger::pg("Wrote " . strlen($sky_bytes) . " bytes to " . $sky_file_path);
        return $skyfile;
    }
    
    function create_wad() {
        Logger::pg("--- WRITING WAD ---");
        $wad_out = new Wad_Handler();
        
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

