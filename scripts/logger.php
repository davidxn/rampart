<?php

require_once("_constants.php");

Logger {
    
    private static $me = null;
    private $txid = 0;
    
    private function __construct() {
        $this->txid = rand(10000,99999);
    }
    
    public static function get_instance() {
        if (self::$me == null) {
            self::$me = new Logger();
        }
        else {
            return self::$me;
        }
    }
    
    public static function lg($string) {
        $logger = self::get_instance();
        $time = date("F d Y H:i:s", time());
        file_put_contents(LOG_FILE, $time . " " . $logger->txid . " " . $string . PHP_EOL, FILE_APPEND);
    }
}