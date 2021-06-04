<?php

set_time_limit(240);

require_once("./_constants.php");
require_once("./scripts/wad_handler.php");
require_once("./scripts/mapinfo_handler.php");
require_once("./scripts/guide_writer.php");

class Project_Compiler {

    public $txid = null;

    function compile_project($bypass_cache) {
        $this->set_status("Initializing");
        $tries = 0;
        $start_time = time();
        
        $lock_file_recent = file_exists(LOCK_FILE_DOWNLOAD) && (time() - filemtime(LOCK_FILE_DOWNLOAD)) < 60;
        $pk3_is_current = file_exists(PK3_FILE) && (filemtime(CATALOG_FILE) < filemtime(PK3_FILE));
        
        if ($lock_file_recent) {
            echo json_encode(['success' => false, 'error' => 'A PK3 is already being generated! Hold on a minute then try again']);
            die();
        }
        if (!$pk3_is_current) {
            $this->lg("Catalog is newer than PK3, so update is required: " . (filemtime(CATALOG_FILE) - filemtime(PK3_FILE)));
        } else {
            if (file_exists(PK3_FILE)) {
                $this->lg("Catalog is older than PK3 - can serve existing one: " . (filemtime(CATALOG_FILE) - filemtime(PK3_FILE)));
            } else {
                $this->lg("No snapshot yet - will create one");
            }
        }

        //If we already have a PK3 that's been generated after the last update of the catalogue file, just serve that
        if (!$bypass_cache && ($lock_file_recent || $pk3_is_current)) {
            echo json_encode(['success' => true, 'newpk3' => false]);
            die();
        }

        //Okay - let's log this download generation!
        @unlink(PK3_GEN_LOG_FILE);

        //Mutex
        file_put_contents(LOCK_FILE_DOWNLOAD, ":)");

        $this->lg("Locked for generating new download");

        $catalog = @json_decode(file_get_contents(CATALOG_FILE), true);
        if (empty($catalog)) {
            $catalog = [];
        }

        $this->set_status("Clearing old work folder");
        $this->clean();
        $this->set_status("Translating uploaded WADs into maps...");
        $this->generate_map_wads($catalog);
        $this->set_status("Generating MAPINFO and other descriptors like that...");
        $this->generate_info($catalog);
        $this->set_status("Copying static content like the hub map and textures...");
        $this->copy_static_content();
        $this->set_status("Fiddling with DIALOGUE to write RAMPO's menus...");
        $this->write_guide_dialogue();
        $this->set_status("Waiting on the ZIP process (this one always takes a minute or so)");
        $this->create_pk3();
        //Unmutex
        @unlink(LOCK_FILE_DOWNLOAD);
        $this->set_status("Complete");
        $this->lg("Download generating lock released");
        
        echo json_encode(['success' => true, 'newpk3' => true]);
    }

    function write_guide_dialogue() {
        $this->lg("Rewriting guide dialogue");
        $hub_map_location = PK3_FOLDER . DIRECTORY_SEPARATOR . HUB_MAP_FILE;
        $wad_in = new Wad_Handler($hub_map_location);
        $wad_out = new Wad_Handler();
        
        //Go through our uploaded WAD and copy all the lumps. Inject our DIALOGUE after BEHAVIOR, and ignore existing DIALOGUE
        foreach ($wad_in->lumps as $lump) {
            $this->lg("Got lump " . $lump['name'] . " from hub WAD");
            if ($lump['name'] == 'DIALOGUE') {
                $this->lg("Throwing that one out");
                continue;
            }
            $wad_out->add_lump($lump);
            if ($lump['name'] == 'BEHAVIOR') {
                $this->lg("Adding new generated DIALOGUE lump");
                $guide_writer = new Guide_Dialogue_Writer();
                $new_dialogue_lump = [];
                $new_dialogue_lump['name'] = 'DIALOGUE';
                $new_dialogue_lump['data'] = $guide_writer->write();
                $wad_out->add_lump($new_dialogue_lump);
            }
        }
        $this->lg("Writing new hub WAD");
        $bytes_written = $wad_out->write_wad($hub_map_location);
        $this->lg("Wrote " . $bytes_written . " bytes to new hub WAD");
    }
    
    /**
     * Writes the MAPINFO and LANGUAGE lumps using the map properties and whether we've found music, skies, etc
     */
    function generate_info($catalog) {
        
        $this->lg("--- GENERATING INFO LUMPS ---");

        //For every map in the catalog, write a MAPINFO entry and LANGUAGE lump.
        $mapinfo = "";
        $language = "[enu default]" . PHP_EOL . PHP_EOL;

        foreach ($catalog as $map_data) {
            
            //Header
            $mapinfo .= "map MAP" . $map_data['map_number'] . " \"" . $map_data['map_name'] . "\"" . PHP_EOL;
            
            //The basics - include the name, author, and point everything to go back to MAP01
            $mapinfo .= "{" . PHP_EOL;
            $mapinfo .= "\t" . "author = \"" . $map_data['author'] . "\"" . PHP_EOL;
            $mapinfo .= "\t" . "cluster = 1" . PHP_EOL;
            $mapinfo .= "\t" . "next = MAP01" . PHP_EOL;
            
            //Skies. If a sky has been included, it'll have been written to our PK3 folder - if now, just default to RSKY1
            $has_sky = false;
            if (file_exists(PK3_FOLDER . "textures/MAP" . $map_data['map_number'] . "/MSKY" . $map_data['map_number'])) {
                $mapinfo .= "\t" . "sky1 = MSKY" . $map_data['map_number'] . PHP_EOL;
                $has_sky = true;
            }
            if (file_exists(PK3_FOLDER . "textures/MAP" . $map_data['map_number'] . "/MSKZ" . $map_data['map_number'])) {
                $mapinfo .= "\t" . "sky2 = MSKZ" . $map_data['map_number'] . PHP_EOL;
            }
            if (!$has_sky) {
                $mapinfo .= "\t" . "sky1 = RSKY1" . PHP_EOL;
            }

            //Use this map's music if it exists, or D_RUNNIN
            if (file_exists(PK3_FOLDER . "music/" . "MUS" . $map_data['map_number'])) {
                $mapinfo .= "\t" . "music = MUS" . $map_data['map_number'] . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "music = D_RUNNIN" . PHP_EOL;
            }

            //Default is to disallow jump/crouch, so add exceptions here if specified
            $map_allows_jump = 0;
            if (isset($map_data['jumpcrouch']) && $map_data['jumpcrouch'] == 1) {
                $map_allows_jump = 1;
            }
            if ($map_allows_jump) {
                $mapinfo .= "\t" . "AllowJump" . PHP_EOL;
                $mapinfo .= "\t" . "AllowCrouch" . PHP_EOL;
            } else {
                $mapinfo .= "\t" . "NoJump" . PHP_EOL;
                $mapinfo .= "\t" . "NoCrouch" . PHP_EOL;
            }
            $mapinfo .= "}" . PHP_EOL;
            $mapinfo .= PHP_EOL;
            
            $map_is_wip = 0;
            if (isset($map_data['wip']) && $map_data['wip'] == 1) {
                $map_is_wip = 1;
            }

            //Now write the map properties we need to read from in the game
            $language .= "MAP" . $map_data['map_number'] . "NAME = \"" . $map_data['map_name'] . "\";" . PHP_EOL;
            $language .= "MAP" . $map_data['map_number'] . "AUTH = \"" . $map_data['author'] . "\";" . PHP_EOL;
            $language .= "MAP" . $map_data['map_number'] . "SP_JUMP = \"" . $map_allows_jump . "\";" . PHP_EOL;
            $language .= "MAP" . $map_data['map_number'] . "SP_WIP = \"" . $map_is_wip . "\";" . PHP_EOL;
            $language .= PHP_EOL;
            $this->lg("Added entries for map " . $map_data['map_number'] . " (" . $map_data['map_name'] . ")");
        }
        
        //All done - output the files
        $language_filename = PK3_FOLDER . "LANGUAGE.ramp";
        $mapinfo_filename = PK3_FOLDER . "MAPINFO.ramp";
        @unlink($mapinfo_filename);
        file_put_contents($mapinfo_filename, $mapinfo);
        $this->lg("Wrote " . $mapinfo_filename);
        @unlink($language_filename);
        file_put_contents($language_filename, $language);
        $this->lg("Wrote " . $language_filename);
    }

    function generate_map_wads($catalog) {
        
        $this->lg("--- GENERATING MAPS ---");
        
        foreach ($catalog as $map_data) {
            $map_file_name = "MAP" . $map_data['map_number'] . ".WAD";
            $this->lg(PHP_EOL . "> MAP" . $map_data['map_number'] . ": Reading source WAD (" . $map_data['map_name'] . ")");
            $source_wad = UPLOADS_FOLDER . $map_file_name;
            $target_wad = PK3_FOLDER . "maps/" . $map_file_name;
            $wad_bytes = file_get_contents($source_wad);
            $wad_handler = new Wad_Handler($source_wad);
            $this->lg($wad_handler->wad_info());

            $music_bytes = "";
            $map_lumps = [];
            $in_map = false;
            foreach ($wad_handler->lumps as $lump) {
                if ($lump['type'] != 'mapdata' && $in_map) {
                    $this->lg("Finished reading map");
                    $in_map = false;
                }
                if (($lump['type'] == 'mapmarker' && !$in_map) || ($lump['type'] == 'mapdata' && $in_map)) {
                    $in_map = true;
                    $map_lumps[] = $lump;
                    $this->lg("Read map lump " . $lump['name'] . " with size " . strlen($lump['data']));
                    continue;
                }
                if (in_array($lump['type'], ['midi', 'ogg', 'mp3']) && !$music_bytes) {
                    $music_bytes = $lump['data'];
                    $music_type = $lump['type'];
                    $this->lg("Music of type " . $lump['type'] . " found in lump " . $lump['name'] . " with size " . strlen($music_bytes));
                    continue;
                }
                //If we have an entry that matches the default sky lump, use that as sky
                if (strtoupper($lump['name']) == DEFAULT_SKY_LUMP) {
                    $sky_bytes = $lump['data'];
                    $this->lg(DEFAULT_SKY_LUMP . " default sky lump found with size " . strlen($sky_bytes));
                    $this->write_sky_to_pk3($map_data['map_number'], 1, $sky_bytes);
                    continue;
                }
                if (strtoupper($lump['name']) == 'MAPINFO') {
                    $this->lg("Found " . $lump['name'] . " lump, parsing it");
                    $mapinfo_handler = new Mapinfo_Handler($lump['data']);
                    $mapinfo_properties = $mapinfo_handler->parse();
                    if (isset($mapinfo_properties['error'])) {
                        $this->lg($mapinfo_properties['error']);
                        continue;
                    }
                    foreach ($mapinfo_properties as $index => $value) {
                        $this->lg("\t" . $index . ": " . $value);
                    }
                    //Check for SKY1 and SKY2
                    for ($i = 1; $i <= 2; $i++) {
                        if (isset($mapinfo_properties['sky' . $i])) {
                            $this->lg("SKY" . $i . " found in MAPINFO");
                            $skylumpname = $mapinfo_properties['sky' . $i];
                            if ($skylump = $wad_handler->get_lump($skylumpname)) {
                                $this->lg("Found lump " . $skylumpname . " pointed to by SKY" . $i . ", including it");
                                $this->write_sky_to_pk3($map_data['map_number'], $i, $skylump['data']);
                            } else {
                                $this->lg("No lump " . $skylumpname . " pointed to by SKY" . $i . ", trusting it's already included");
                            }
                        }
                    }
                }
                if (in_array(strtoupper($lump['name']), ['DECORATE', 'ZSCRIPT'])) {
                    if (strpos($lump['data'], "replaces") !== false) { //Okay, I don't have time to write a proper parser
                        $this->lg("Found " . $lump['name'] . " lump but refusing it as it performs replacements!");
                    } else {
                        $this->lg("Found " . $lump['name'] . " lump, throwing it into the root");
                        @mkdir(PK3_FOLDER);
                        $script_file_path = PK3_FOLDER . "/" . strtoupper($lump['name']) . "." . $map_data['map_number'];
                        file_put_contents($script_file_path, $lump['data']);
                        $this->lg("Wrote " . strlen($lump['data']) . " bytes to " . $script_file_path);
                    }
                }
            }
            
            //Write our music if we have it
            if ($music_bytes) {
                $music_path = PK3_FOLDER . "music/" . "MUS" . $map_data['map_number'];
                file_put_contents($music_path, $music_bytes);
                $this->lg("Wrote " . strlen($music_bytes) . " bytes to " . $music_path);
            }

            //Construct a new WAD using only the map lumps
            $wad_writer = new Wad_Handler();
            foreach ($map_lumps as $lump) {
                $wad_writer->add_lump($lump);
            }
            
            @unlink($target_wad);
            $bytes_written = $wad_writer->write_wad($target_wad);
            $this->lg("Wrote " . $bytes_written . " bytes to " . $target_wad);
        }
    }
    
    function write_sky_to_pk3($mapnum, $skynum, $sky_bytes) {
        $skyfile = "MSKY";
        if ($skynum == 2) {
            $skyfile = "MSKZ";
        }
        $folder = PK3_FOLDER . "textures/MAP" . $mapnum;
        @mkdir($folder);
        $sky_file_path = $folder . "/" . $skyfile . $mapnum;
        file_put_contents($sky_file_path, $sky_bytes);
        $this->lg("Wrote " . strlen($sky_bytes) . " bytes to " . $sky_file_path);
    }

    function create_pk3() {
        if (!empty(ZIP_SCRIPT)) {
            $this->lg("--- ASKING EXTERNAL SCRIPT TO ZIP PK3 ---");
            exec(ZIP_SCRIPT);
            $this->lg("Script finished");
            return;
        }
        
        $this->lg("--- CREATING PK3 ---");
        // Get real path for our folder
        $rootPath = realpath(PK3_FOLDER);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open(PK3_FILE, ZipArchive::CREATE | ZipArchive::OVERWRITE);

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
                $this->lg("Zipped " . $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
        $this->lg("Wrote ZIP file");
    }
    
    function copy_static_content() {

        $this->lg("--- COPYING STATIC CONTENT ---");

        $source = STATIC_CONTENT_FOLDER;
        $dest= PK3_FOLDER;

        foreach (
         $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
          \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
          if ($item->isDir()) {
            @mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            $this->lg("Copied static content " . $item);
          }
        }
    }

    function clean() {
        if (strlen(PK3_FOLDER) < 10) {
            return;
        }
        $this->deleteAll(PK3_FOLDER);
        $this->lg("Cleaned PK3 folder");
        mkdir(PK3_FOLDER);
        foreach (PK3_REQUIRED_FOLDERS as $folder) {
            mkdir(PK3_FOLDER . $folder);
        }
    }

    function deleteAll($str) {      
        if (is_file($str)) {              
            return unlink($str);
            //echo($str) . PHP_EOL;
        }
        elseif (is_dir($str)) {
            $scan = glob(rtrim($str, '/').'/*');
            foreach($scan as $index=>$path) {
                $this->deleteAll($path);
            }
            return @rmdir($str);
            //echo($str) . PHP_EOL;
        }
    }

    function lg($string) {
        if ($this->txid == null) {
            $this->txid = rand(10000,99999);
        }
        $time = date("F d Y H:i:s", time());
        file_put_contents(PK3_GEN_LOG_FILE, $time . " " . $this->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }
    
    function set_status($string) {
        file_put_contents(STATUS_FILE, $string);
    }
}

$nocache = isset($_GET['nocache']) ? $_GET['nocache'] : false;

$handler = new Project_Compiler();
$handler->compile_project($nocache);
