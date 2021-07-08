<?php
require_once("_constants.php");

class Mapinfo_Handler {
    
    private $bytes = null;
    
    public function __construct($bytes) {
        $this->bytes = $bytes;
    }

    public function parse() {
        
        //This is incredibly basic - replace this with the equivalent of the parser from UDB eventually...
        $parsed_data = [];
        $mapinfo_lines = split(PHP_EOL, $this->bytes);
        foreach ($mapinfo_lines as $line) {
            $line = trim($line);
            if ($this->clean_token($line) == 'nojump' || $this->clean_token($line) == 'nocrouch') {
                $parsed_data['jumpcrouch'] = 0;
            }
            if ($this->clean_token($line) == 'allowjump' || $this->clean_token($line) == 'allowcrouch') {
                $parsed_data['jumpcrouch'] = 1;
            }
            
            $line_tokens = split("=", $line);

            $key = $this->clean_token($line_tokens[0]);
            if (in_array($key, ALLOWED_MAPINFO_PROPERTIES)) {
                if (!isset($line_tokens[1])) {
                    $value = "_SET_";
                } else {
                    $value = $this->clean_token($line_tokens[1]);
                }
                $parsed_data[$key] = $value;
            }
            
            if (in_array($key, ['sky1', 'skybox'])) {
                $value = $this->strip_quotes($line_tokens[1]);
                $terminatechar = strpos($value, ",");
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
        }
        
        return($parsed_data);
    }
    
    public function strip_quotes($str) {
        $str = trim($str);
        return str_replace(["\"", "'"], "", $str);
    }
    
    public function clean_token($str) {
        return strtolower(trim($str));
    }
    
}

/*
$mapinfo = "map MAP01 \"Brick and Root\"
{
	Levelnum = 1
	skybox = \"OSKY12\"
	SKY1 = \"OSKY12\"
	SKY2 = \"OSKY33\"
	Music = \"TREEROOT\"
	Par = 600
	NoJump
	NoCrouch
	ResetHealth
	ResetInventory
}";
$handler = new Mapinfo_Handler($mapinfo);
$handler->parse();
**/