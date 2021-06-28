<?
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