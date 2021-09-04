<?php

require_once("_constants.php");

class Logger {
    
    private static $me = null;
    private $txid = 0;
    
    private function __construct() {
        $this->txid = rand(10000,99999);
    }
    
    public static function get_instance() {
        if (self::$me == null) {
            self::$me = new Logger();
        }
        return self::$me;
    }
    
    //Normal log file
    public static function lg($string) {
        $logger = self::get_instance();
        $time = date("F d Y H:i:s", time());
        file_put_contents(LOG_FILE, $time . " " . $logger->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }
    
    //PK3 generation log
    public static function pg($string) {
        $logger = self::get_instance();
        $time = date("F d Y H:i:s", time());
        file_put_contents(PK3_GEN_LOG_FILE, $time . " " . $logger->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }
    
    public static function clear_pk3_log() {
        @unlink(PK3_GEN_LOG_FILE);
    }
    
    public static function record_pk3_generation($start_time, $seconds) {
        file_put_contents(PK3_GEN_HISTORY_FILE, $start_time . "," . max($seconds, 1) . "," . @filesize(get_project_full_path()) . PHP_EOL, FILE_APPEND);
    }
}