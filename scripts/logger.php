<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

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
    public static function pg($string, $map_number = 0, $is_error = false) {
        $logger = self::get_instance();
        $time = date("F d Y H:i:s", time());
        $log_line = $time . " " . $logger->txid . " " . $string . PHP_EOL;
        file_put_contents(PK3_GEN_LOG_FILE, $log_line, FILE_APPEND);
        @mkdir(PK3_GEN_LOG_FOLDER, 0777, true);
        if ($map_number > 0) {
            file_put_contents(Logger::get_map_log_file($map_number), $log_line, FILE_APPEND);
            if ($is_error) {
                file_put_contents(Logger::get_map_error_file($map_number), $log_line, FILE_APPEND);
            }
        }
    }
    
    //Individual map generation files
    public static function clear_pk3_log() {
        @unlink(PK3_GEN_LOG_FILE);
        @mkdir(PK3_GEN_LOG_FOLDER, 0777, true);
        $logfiles = scandir(PK3_GEN_LOG_FOLDER);
        foreach ($logfiles as $logfile) {
            if (substr($logfile, 0, 10) == "rampartlog") {
                @unlink(PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . $logfile);
            }
        }
    }
    
    public static function clear_log_for_map($map_number) {
        @mkdir(PK3_GEN_LOG_FOLDER, 0777, true);
        @unlink(PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . "rampartlog" . $map_number . ".log");
        @unlink(PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . "rampartlog" . $map_number . ".err");
    }
    
    public static function map_has_log($map_number) {
        return file_exists(Logger::get_map_log_file($map_number));
    }

    public static function map_has_errors($map_number) {
        return file_exists(PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . "rampartlog" . $map_number . ".err");
    }
    
    public static function get_map_log_file($map_number) {
        return PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . "rampartlog" . $map_number . ".log";
    }

    public static function get_map_error_file($map_number) {
        return PK3_GEN_LOG_FOLDER . DIRECTORY_SEPARATOR . "rampartlog" . $map_number . ".err";
    }
        
    public static function record_pk3_generation($start_time, $seconds_array) {
        $times_string = implode(",", $seconds_array);
        file_put_contents(PK3_GEN_HISTORY_FILE, $start_time . "," . $times_string . "," . @filesize(get_project_full_path()) . PHP_EOL, FILE_APPEND);
    }
    
    public static function record_upload($start_time, $map_number, $size) {
        file_put_contents(UPLOAD_LOG_FILE, $start_time . "," . $map_number . "," . $size . PHP_EOL, FILE_APPEND);
    }

    public static function get_log_link($map_number) {
        
        $log_link = "";
        if (Logger::map_has_log($map_number)) {
            $log_link = '<a href="/maplog.php?id=' . $map_number . '"><button class="property property-log"></button></a>';
        }
        if (Logger::map_has_errors($map_number)) {
            $log_link = '<a href="/maplog.php?id=' . $map_number . '"><button class="property property-logerror"></button></a>';
        }
        return $log_link;
    }
    
    public static function save_build_info($data, $lump_guardian) {
        $data['global_ambient_list'] = $lump_guardian->global_ambient_list;
        $json = json_encode($data);
        @file_put_contents(WORK_FOLDER . DIRECTORY_SEPARATOR . "buildinfo.log", $json);
    }
}