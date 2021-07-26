<?php
const RAMPART_HOME = __DIR__ . DIRECTORY_SEPARATOR;

//////////////////////////////////////
// Enable or disable uploads and edits
//////////////////////////////////////

const ALLOW_NEW_UPLOADS = true;
const ALLOW_EDIT_UPLOADS = true;

//////////////////////////////////////////////////////////////////////////////////
// To protect against spam, limits in seconds for one IP trying PINs and uploading
//////////////////////////////////////////////////////////////////////////////////

const PIN_MANAGER_CLASS = "Pin_Manager_Random";
const PIN_ATTEMPT_GAP = 120;
const UPLOAD_ATTEMPT_GAP = 120;

///////////////////////////////////////////////////////////
// Details about your hub map and how to assign map numbers
///////////////////////////////////////////////////////////

const HUB_MAP_FILE          = "maps/MAP01.wad";
const HUB_SLOTS             = 200;
const FIRST_USER_MAP_NUMBER = 10;

////////////////////////////////////////////////////////////
// Details about the location and name of the output project
////////////////////////////////////////////////////////////

const PROJECT_FILE_NAME     = "PROJECT-SNAPSHOT.pk3";
const PROJECT_OUTPUT_FOLDER = RAMPART_HOME . "out/";
const PK3_FILE              = PROJECT_OUTPUT_FOLDER . PROJECT_FILE_NAME;
const DEFAULT_SKY_LUMP      = "RSKY1";

////////////////////////////////////////
// Which properties to detect in MAPINFO
////////////////////////////////////////

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
// Details for the computer guide script to add to DIALOGUE
///////////////////////////////////////////////////////////

const GUIDE_ENABLED = true;
const GUIDE_NAME = "Map Guide";
const GUIDE_TEXT = "Which map can I help you navigate to today?";
const GUIDE_MENU_CLASS = "";
const GUIDE_SCRIPT_NUMBER = 1;
const CLOSE_TEXT = "Close (ESC)";
const MAPS_PER_PAGE = 8;

///////////////////////////////////////////////////////////
// RAMPART constants that you probably don't need to modify
///////////////////////////////////////////////////////////

const RAMPART_VERSION = "BETA 0.9";

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
const PK3_GEN_HISTORY_FILE  = RAMPART_HOME . "work/pk3history.log";

const LOCK_FILE_UPLOAD      = RAMPART_HOME . "work/.uploadlockfile";
const LOCK_FILE_COMPILE     = RAMPART_HOME . "work/.compilelockfile";
const STATUS_FILE           = RAMPART_HOME . "status.log";

const ZIP_SCRIPT            = "cd " . PK3_FOLDER . " && zip -FSr1 " . PK3_FILE . " *";
