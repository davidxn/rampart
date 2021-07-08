<?
<<<<<<< HEAD

/////////////////////////////////
// Site enable/disable
/////////////////////////////////

const ALLOW_NEW_UPLOADS = false;
const ALLOW_EDIT_UPLOADS = true;

///////////////////////////////////////////////////////////
// Details about your hub map and how to assign map numbers
///////////////////////////////////////////////////////////

const HUB_MAP_FILE          = "maps/MAP01.wad";
const HUB_SLOTS             = 200;
const FIRST_USER_MAP_NUMBER = 10;

////////////////////////////////////////////////////////////
// Details about the location and name of the output project
////////////////////////////////////////////////////////////

const PROJECT_FILE_NAME     = "RAMP-SNAPSHOT.pk3";
const PK3_FILE              = "/tmpramp/" . PROJECT_FILE_NAME;
const DEFAULT_SKY_LUMP      = "RSKY1";

////////////////////////////////////////
// Which properties to detect in MAPINFO
////////////////////////////////////////
const ALLOWED_MAPINFO_PROPERTIES = [
    'lightmode',
    'map07special',
    'music',
    'SpiderMastermindSpecial',
    'SpecialAction_ExitLevel',
    'SpecialAction_OpenDoor',
    'SpecialAction_LowerFloor',
    'SpecialAction_KillMonsters',
    'BaronSpecial',
    'CyberdemonSpecial',
    'SpiderMastermindSpecial'
];

///////////////////////////////////////////////////////////
// Details for the computer guide script to add to DIALOGUE
///////////////////////////////////////////////////////////

const GUIDE_ENABLED = true;
const GUIDE_NAME = "RAMPO";
const GUIDE_TEXT = "Hello! I'm RAMPO (which stands for RAMP Assistant for Map Pointing-Out). Which map can I help you navigate to today?";
const GUIDE_MENU_CLASS = "RampGuideMenu";
const GUIDE_SCRIPT_NUMBER = 1;
const CLOSE_TEXT = "Close (ESC)";
const MAPS_PER_PAGE = 8;

////////////////////////////////////////////////////////
// RAMP constants that you probably don't need to modify
////////////////////////////////////////////////////////

const RAMPART_VERSION = "BETA";
const RAMPART_HOME = __DIR__ . DIRECTORY_SEPARATOR;

const PK3_REQUIRED_FOLDERS  = ["music", "maps", "textures"];

const CATALOG_FILE          = RAMPART_HOME . "work/catalog.json";
const PIN_FILE              = RAMPART_HOME . "work/pins.txt";
const PIN_MASTER_FILE       = RAMPART_HOME . "work/pins-master.txt";
const IPS_FOLDER            = RAMPART_HOME . "work/ips/";
const UPLOADS_FOLDER        = RAMPART_HOME . "work/uploads/";
const STATIC_CONTENT_FOLDER = RAMPART_HOME . "work/fixedcontent/";
const PK3_FOLDER            = RAMPART_HOME . "work/pk3/";
const LOG_FILE              = RAMPART_HOME . "work/log.log";
const PK3_GEN_LOG_FILE      = RAMPART_HOME . "work/pk3generation.log";

const LOCK_FILE_UPLOAD      = RAMPART_HOME . "work/.uploadlockfile";
const LOCK_FILE_COMPILE     = RAMPART_HOME . "work/.compilelockfile";
const STATUS_FILE           = RAMPART_HOME . "status.log";

const ZIP_SCRIPT            = "cd " . PK3_FOLDER . " && zip -FSr1 " . PK3_FILE . " *";
=======
const RAMPART_HOME = __DIR__ . DIRECTORY_SEPARATOR;

const PK3_REQUIRED_FOLDERS  = ["music", "maps", "textures"];

const CATALOG_FILE          = "./work/catalog.json";
const PIN_FILE              = "./work/pins.txt";
const PIN_MASTER_FILE       = "./work/pins-master.txt";
const MAP_NUM_FILE          = "./work/nextmapnum.txt";
const IPS_FOLDER            = "./work/ips/";
const UPLOADS_FOLDER        = "./work/uploads/";
const STATIC_CONTENT_FOLDER = "./work/fixedcontent/";

const FIRST_USER_MAP_NUMBER = 10;

const PROJECT_FILE_NAME     = "RAMP-SNAPSHOT.pk3";
const PK3_FOLDER            = "./work/pk3/";
const PK3_FILE              = "/tmpramp/" . PROJECT_FILE_NAME;

const LOG_FILE              = "./work/log.log";
const PK3_GEN_LOG_FILE      = RAMPART_HOME . "work/pk3generation.log";
const STATUS_FILE           = "./status.log";

const LOCK_FILE_UPLOAD      = "./work/.uploadlockfile";
const LOCK_FILE_DOWNLOAD    = "./work/.downloadlockfile";

const DEFAULT_SKY_LUMP      = "RSKY1";
const ZIP_SCRIPT            = "cd " . RAMPART_HOME . PK3_FOLDER . " && zip -FSr1 " . PK3_FILE . " *";

const HUB_SLOTS             = 120;
const HUB_MAP_FILE          = "maps/MAP01.wad";

const MAPS_PER_PAGE         = 7;

const ALLOWED_MAPINFO_PROPERTIES = ['lightmode', 'map07special', 'music'];

const GUIDE_NAME = "RAMPO";
const GUIDE_TEXT = "Hello! I'm RAMPO (which stands for RAMP Assistant for Map Pointing-Out). Which map can I help you navigate to today?";
const CLOSE_TEXT = "Close (ESC)";
>>>>>>> 715c9beefc57e1cc21821b0324eb0bceaaad9f1b
