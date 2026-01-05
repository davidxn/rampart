<?php
const RAMPART_HOME = __DIR__ . DIRECTORY_SEPARATOR;
const SETTINGS_FILE = RAMPART_HOME . "_settings.json";

$setting_defaults = [
    # About the project
    "PROJECT_TITLE" => "Rampart Project",
    "PROJECT_FORMAT" => "WAD",
    "PROJECT_FILE_NAME" => "MYPROJECT",
    "PROJECT_OUTPUT_FOLDER" => RAMPART_HOME . "out",
    "ALLOW_NEW_UPLOADS" => false,
    "ALLOW_EDIT_UPLOADS" => true,
    "HUB_MAP_NAME" => "",
    "ALLOW_SNAPSHOT_DOWNLOAD" => true,
    "DOOM_VERSION" => "UZDoom",
    
    # About uploads
    "ALLOW_CONTENT_MAPS" => true,
    "ALLOW_CONTENT_MUSIC" => true,
    "ALLOW_CONTENT_SCRIPTS" => true,
    "ALLOW_CONTENT_SOUND" => true,
    "ZSCRIPT_VERSION" => "4.7.1",
    
    "ALLOW_GAMEPLAY_JUMP" => 'never',
    
    "DEFAULT_SKY_LUMP" => "RSKY1",
    "DEFAULT_MUSIC_LUMP" => "D_RUNNIN",
    
    "NOTIFY_ON_MAPS" => 'never',
    "NOTIFY_EMAIL" => "",
    
    # About MAPINFO
    "PROJECT_WRITE_MAPINFO" => false,
    "PROJECT_MAPINFO_PROPERTIES" =>
'lightmode
map07special
music
baronspecial
cyberdemonspecial
spidermastermindspecial
specialaction_exitlevel
specialaction_opendoor
specialaction_lowerfloor
specialaction_killmonsters
',
    "PROJECT_QUICK_MUSIC" => false,
    "GENERATE_MARQUEES" => false,
    "MUSIC_LUMP_MAP" => "none",

    # About the site
    "BANNER_MESSAGE" => "",
    "PIN_ATTEMPT_GAP" => 60,
    "UPLOAD_ATTEMPT_GAP" => 60,
    "PIN_MANAGER_CLASS" => "Pin_Manager_Random",
    
    # The guide
    "GUIDE_ENABLED" => false,
    "GUIDE_CLASS" => "MapGuide",
    "GUIDE_NAME" => "Map Guide",
    "GUIDE_TEXT" => "Which map can I help you navigate to today?",
    "GUIDE_MENU_CLASS" => "",
    "GUIDE_SCRIPT_NUMBER" => 1,
    "GUIDE_CLOSE_TEXT" => "Close (ESC)",
    "MAPS_PER_PAGE" => 8,
];

function get_setting($setting, $type = null) {
    if (isset($GLOBALS['settings'][$setting])) {
        //Stupid special cases
        if (strtolower($GLOBALS['settings'][$setting]) == "true") { return true; }
        if (strtolower($GLOBALS['settings'][$setting]) == "false") { return false; }
        return $GLOBALS['settings'][$setting];
    } else if (isset($GLOBALS['setting_defaults'][$setting])) {
        return $GLOBALS['setting_defaults'][$setting];
    }
    return null;
}

$string_to_decode = "[]";
if (file_exists(SETTINGS_FILE)) {
    $string_to_decode = file_get_contents(SETTINGS_FILE);
}
$settings = json_decode($string_to_decode, true);
if (json_last_error() != JSON_ERROR_NONE) {
    die("Settings file appears not to be working - amend or delete _settings.json to reset things");
}
if (!$settings) {
    $settings = [];
}

function get_project_full_path(): string {
    return get_setting("PROJECT_OUTPUT_FOLDER") . DIRECTORY_SEPARATOR . get_setting("PROJECT_FILE_NAME") . "." . strtolower(get_setting("PROJECT_FORMAT"));
}


// Must be lowercase!!
const ALLOWED_MAPINFO_PROPERTIES = [
    'activateowndeathspecials',
    'aircontrol',
    'airsupply',
    'allowmonstertelefrags',
    'baronspecial',
    'checkswitchrange',
    'compat_sectorsounds',
    'cyberdemonspecial',
    'doublesky',
    'evenlighting',
    'eventhandlers',
    'F1',
    'fade',
    'fogdensity',
    'gravity',
    'lightblendmode',
    'lightmode',
    'lightning',
    'map07special',
    'music',
    'sky1',
    'sky2',
    'skyfog',
    'spidermastermindspecial',
    'specialaction_exitlevel',
    'specialaction_opendoor',
    'specialaction_lowerfloor',
    'specialaction_killmonsters',
    'useplayerstartz'
];

///////////////////////////////////////////////////////////
// RAMPART constants that you probably don't need to modify
///////////////////////////////////////////////////////////

const RAMPART_VERSION = "2.0 beta";

const PK3_REQUIRED_FOLDERS  = ["music", "maps", "textures"];

const WORK_FOLDER           = RAMPART_HOME . "work/";
const DATA_FOLDER           = RAMPART_HOME . "data/";

const CATALOG_FILE          = WORK_FOLDER . "catalog.json";
const PIN_FILE              = WORK_FOLDER . "pins.txt";
const PIN_MASTER_FILE       = WORK_FOLDER . "pins-master.txt";
const IPS_FOLDER            = WORK_FOLDER . "ips/";
const UPLOADS_FOLDER        = WORK_FOLDER . "uploads/";
const PK3_FOLDER            = WORK_FOLDER . "pk3/";
const LOG_FILE              = WORK_FOLDER . "log.log";
const PK3_GEN_LOG_FILE      = WORK_FOLDER . "pk3generation.log";
const PK3_GEN_LOG_FOLDER    = WORK_FOLDER . "pk3genlogs";
const PK3_GEN_HISTORY_FILE  = WORK_FOLDER . "pk3history.log";
const UPLOAD_LOG_FILE       = WORK_FOLDER . "uploads.log";
const SNAPSHOT_ID_FILE      = WORK_FOLDER . "snapshot.id";
const BUILD_INFO_FILE       = WORK_FOLDER . "buildinfo.log";

const BLANK_MAP             = DATA_FOLDER . "NOMAP.WAD";
const RESOURCE_WAD_FOLDER   = DATA_FOLDER . "resourcewads/";
const STATIC_CONTENT_FOLDER = DATA_FOLDER . "fixedcontent/";

const MAPS_FOLDER           = PK3_FOLDER . "maps/";

const LOCK_FILE_UPLOAD      = WORK_FOLDER . ".uploadlockfile";
const LOCK_FILE_COMPILE     = WORK_FOLDER . ".compilelockfile";
const STATUS_FILE           = RAMPART_HOME . "status.log";

const PASSWORD_FILE         = DATA_FOLDER . "/" . "rampartpass.php";

$ZIP_SCRIPT                 = "cd " . PK3_FOLDER . " && zip -FSr1 " . get_project_full_path() . " *";
$ZIP_SCRIPT_WINDOWS         = "cd /d " . PK3_FOLDER . " && \"c:\\Program Files\\7-Zip\\7z.exe\" a " . get_project_full_path() . " *";
$STATIC_CONTENT_MTIME_SCRIPT= "find " . STATIC_CONTENT_FOLDER . " -type f -printf \"%T@\\n\" | sort | tail -1";

const COMPILE_ERRORS = [
    'ERR_DISABLED' => 'This level has been temporarily disabled due to an issue and will not be included in the compiled project',
    'ERR_WAD_MISSING' => '$1 does not exist in uploads folder, skipping it',
    'ERR_WAD_NO_LUMPS' => 'No map lumps read after map marker, possible malformed WAD. Map will not be included',
    'ERR_WAD_BAD_LUMPS' => 'Unexpected lump $1 in map definition, will not include this map',
    'ERR_TEX_DEFINITION_NOT_NEEDED' => 'You don\'t need to define dummy texture $1 - skipping it',
    'ERR_TEX_REDEFINITION_BASE' => 'TEXTURES attempts to redefine $1 $2 as $3. Already defined in IWAD as $4. Skipping this definition',
    'ERR_TEX_REDEFINITION_OTHER' => 'TEXTURES attempts to redefine $1 $2 as $3. Already defined in map $4 as $5. Skipping this definition',
    'WARN_TEX_REDEFINITION' => 'TEXTURES redefines Texture $1 as $2, already defined in map $3 with an identical definition. Skipping this definition',
    'ERR_TEX_CONFLICTS' => 'Found conflicts while importing TEXTURES lump',
    
    'ERR_LUMP_DUPLICATE_BASE' => 'Lump $1 would overwrite existing lump of that name from the project\'s base resources. Please rename it',
    'ERR_LUMP_DUPLICATE_OTHER' => 'Lump $1 would overwrite existing lump of that name from map number $2. Please rename it to make sure both maps work correctly',
    'WARN_LUMP_DUPLICATE_OTHER' => 'Lump $1 matches same name from map number $2, but the data is identical',
    'ERR_LUMP_NEEDS_CONVERTED' => '$1 lumps are unsupported - convert to $2 with SLADE3',
    'ERR_LUMP_COLORMAP_UNSUPPORTED' => 'Found colormap-related lump $1, but colormaps are not supported',
    'ERR_LUMP_LOCKDEFS_CLEARLOCKS' => 'Found LOCKDEFS lump but refusing it as it performs CLEARLOCKS!',
    
    'ERR_SOUND_SNDINFO_REDEFINITION' => 'SNDINFO tries to define the sound $1 as $2, but it\'s already defined as $3',
    'WARN_SOUND_SNDINFO_REDEFINITION' => 'SNDINFO defines the sound $1, which is already defined but it matches the existing definition $2',
    'WARN_SOUND_SNDINFO_NOT_IMPORTED' => 'Not importing this SNDINFO',
    'ERR_MUSIC_TOO_BIG' => 'Music lump $1 is too large, skipping it. Replace it or re-encode at a lower bitrate',
    'ERR_SOUND_SNDSEQ_CONFLICTS' => 'Found conflicts while importing SNDSEQ lump',
    
    'ERR_DOOMEDNUM_CONFLICT' => 'DoomedNum conflict: $1 for $2 already refers to $3 from map $4, rejecting it',
    'ERR_DOOMEDNUM_CONFLICT_BASE' => 'DoomedNum conflict: $1 for $2 is in a reserved range, rejecting it',
    'ERR_SCRIPT_REPLACEMENTS' => 'Found $1 script lump but refusing it as it performs replacements!',
    'ERR_DECORATE_DOOMEDNUM_CONFLICT' => 'Found DECORATE lump but got a DoomEdNum conflict, not including this script',
    'ERR_IDENTIFIERS_NOT_IMPORTED' => 'Not importing identifiers because scripts for this map were rejected'
];