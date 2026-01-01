<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Mapinfo_Handler {
    
    private string $bytes;
    private int $current_line_number = 0;
    private array $mapinfo_lines = [];
    
    public function __construct($bytes) {
        $this->bytes = $bytes;
    }

    public function parse() {
        
        //This is incredibly basic - replace this with the equivalent of the parser from UDB eventually...
        $parsed_data = [];
        
        //Remove anything between multi-line comments
        $this->bytes = preg_replace("/\/\*[\s\S]*\*\//", "", $this->bytes);
        $this->mapinfo_lines = explode(PHP_EOL, $this->bytes);
        
        while ($this->current_line_number < count($this->mapinfo_lines)) {
            $line = trim($this->mapinfo_lines[$this->current_line_number]);

            if(str_starts_with($this->clean_token($line), "doomednums")) {
                Logger::pg("Found doomednums");
                $parsed_data['doomednums'] = $this->parseDoomedNums();
                continue;
            }
            
            if(str_starts_with($this->clean_token($line), "spawnnums")) {
                Logger::pg("Found spawnnums");
                $parsed_data['spawnnums'] = $this->parseSpawnNums();
                continue;
            }

            if ($this->clean_token($line) == 'nojump' || $this->clean_token($line) == 'nocrouch') {
                $parsed_data['jumpcrouch'] = 0;
            }
            if ($this->clean_token($line) == 'allowjump' || $this->clean_token($line) == 'allowcrouch') {
                $parsed_data['jumpcrouch'] = 1;
            }
            
            $line_tokens = explode("=", $line);

            $key = $this->clean_token($line_tokens[0]);
            if (in_array($key, ALLOWED_MAPINFO_PROPERTIES) || $key == 'music') {
                if (!isset($line_tokens[1])) {
                    $value = "_SET_";
                } else {
                    $value = $this->clean_token($line_tokens[1]);
                }
                $parsed_data[$key] = $value;
            }
            
            if (in_array($key, ['sky1', 'skybox'])) {
                $value = $this->strip_quotes($line_tokens[1]);
                $terminatechar = min(strpos($value, ","), strpos($value, " "));
                if ($terminatechar) {
                    $value = trim(substr($value, 0, $terminatechar));
                }
                $parsed_data['sky1'] = $value;
            }
            if ($key == 'sky2') {
                $value = $this->strip_quotes($line_tokens[1]);
                $terminatechar = strpos($value, ",");
                if ($terminatechar) {
                    $value = trim(substr($value, 0, $terminatechar));
                }
                $parsed_data['sky2'] = $value;
            }
            
            $this->current_line_number++;
        }
        
        return($parsed_data);
    }
    
    public function parseDoomedNums() {
        
        $doomedNums = [];
        $this->current_line_number++;
        $line = "";
        while ($line != "}" && $this->current_line_number < count($this->mapinfo_lines)) {
            $line = trim($this->mapinfo_lines[$this->current_line_number]);
            $line_tokens = explode("=", $line);
            if (count($line_tokens) < 2) {
                //Doesn't have two tokens around equals? Forget it
                $this->current_line_number++;
                continue;
            }
            $number = $this->clean_token($line_tokens[0]);
            $classname = $this->strip_quotes($this->clean_token($line_tokens[1]));
            if (!is_numeric($number)) {
                $this->current_line_number++;
                continue;
            }
            $doomedNums[$number] = $classname;
            $this->current_line_number++;
        }
        return $doomedNums;
    }
    
    public function parseSpawnNums() {
        
        $spawnNums = [];
        $this->current_line_number++;
        $line = "";
        while ($line != "}" && $this->current_line_number < count($this->mapinfo_lines)) {
            $line = trim($this->mapinfo_lines[$this->current_line_number]);
            $line_tokens = explode("=", $line);
            if (count($line_tokens) < 2) {
                //Doesn't have two tokens around equals? Forget it
                $this->current_line_number++;
                continue;
            }
            $number = $this->clean_token($line_tokens[0]);
            $classname = $this->strip_quotes($this->clean_token($line_tokens[1]));
            if (!is_numeric($number)) {
                $this->current_line_number++;
                continue;
            }
            $spawnNums[$number] = $classname;
            $this->current_line_number++;
        }
        return $spawnNums;
    }
    
    public function strip_quotes($str) {
        $str = trim($str);
        return str_replace(["\"", "'"], "", $str);
    }
    
    public function clean_token($str) {
        return strtolower(trim($str));
    }
    
}
