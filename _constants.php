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
    
    # About uploads
    "ALLOW_CONTENT_MAPS" => true,
    "ALLOW_CONTENT_MUSIC" => true,
    "ALLOW_CONTENT_SCRIPTS" => true,
    
    "ALLOW_GAMEPLAY_JUMP" => 'never',
    
    "DEFAULT_SKY_LUMP" => "RSKY1",
    
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

    # About the site
    "BANNER_MESSAGE" => "",
    "PIN_ATTEMPT_GAP" => 60,
    "UPLOAD_ATTEMPT_GAP" => 60,
    "PIN_MANAGER_CLASS" => "Pin_Manager_Random",
    
    # The guide
    "GUIDE_ENABLED" => false,
    "HUB_MAP_FILE" => "maps/MAP01.wad",
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

const FIRST_USER_MAP_NUMBER = 1;

function get_project_full_path() {
    return get_setting("PROJECT_OUTPUT_FOLDER") . DIRECTORY_SEPARATOR . get_setting("PROJECT_FILE_NAME") . "." . strtolower(get_setting("PROJECT_FORMAT"));
}


// Must be lowercase!!
const ALLOWED_MAPINFO_PROPERTIES = [
    'lightmode',
    'map07special',
    'music',
    'spidermastermindspecial',
    'specialaction_exitlevel',
    'specialaction_opendoor',
    'specialaction_lowerfloor',
    'specialaction_killmonsters',
    'baronspecial',
    'cyberdemonspecial',
    'spidermastermindspecial'
];

///////////////////////////////////////////////////////////
// RAMPART constants that you probably don't need to modify
///////////////////////////////////////////////////////////

const RAMPART_VERSION = "BETA 0.9";

const PK3_REQUIRED_FOLDERS  = ["music", "maps", "textures"];

const WORK_FOLDER           = RAMPART_HOME . "work/";
const DATA_FOLDER           = RAMPART_HOME . "data/";

const CATALOG_FILE          = WORK_FOLDER . "catalog.json";
const PIN_FILE              = WORK_FOLDER . "pins.txt";
const PIN_MASTER_FILE       = WORK_FOLDER . "pins-master.txt";
const IPS_FOLDER            = WORK_FOLDER . "ips/";
const UPLOADS_FOLDER        = WORK_FOLDER . "uploads/";
const STATIC_CONTENT_FOLDER = WORK_FOLDER . "fixedcontent/";
const PK3_FOLDER            = WORK_FOLDER . "pk3/";
const LOG_FILE              = WORK_FOLDER . "log.log";
const PK3_GEN_LOG_FILE      = WORK_FOLDER . "pk3generation.log";
const PK3_GEN_HISTORY_FILE  = WORK_FOLDER . "pk3history.log";
const UPLOAD_LOG_FILE       = WORK_FOLDER . "uploads.log";
const SNAPSHOT_ID_FILE      = WORK_FOLDER . "snapshot.id";

const BLANK_MAP             = DATA_FOLDER . "NOMAP.WAD";

const MAPS_FOLDER           = PK3_FOLDER . "maps/";

const LOCK_FILE_UPLOAD      = WORK_FOLDER . ".uploadlockfile";
const LOCK_FILE_COMPILE     = WORK_FOLDER . ".compilelockfile";
const STATUS_FILE           = RAMPART_HOME . "status.log";

const PASSWORD_FILE         = RAMPART_HOME . "admin/rampartpass";

$ZIP_SCRIPT                 = "cd " . PK3_FOLDER . " && zip -FSr1 " . get_project_full_path() . " *";
