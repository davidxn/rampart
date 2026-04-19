<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Mapinfo_Handler {
    
    private string $bytes;
    private int $current_line_number = 0;
    private array $mapinfo_lines = [];
    
    public function __construct($bytes) {
        $this->bytes = $bytes;
    }

    public function parse(): array
    {
        //This is incredibly basic - replace this with the equivalent of the parser from UDB eventually...
        $parsed_data = [];
        
        //Remove anything between multi-line comments
        $this->bytes = preg_replace("/\/\*[\s\S]*\*\//", "", $this->bytes);
        $this->mapinfo_lines = explode("\n", $this->bytes);
        
        while ($this->current_line_number < count($this->mapinfo_lines)) {
            $line = trim($this->mapinfo_lines[$this->current_line_number]);

            if(str_starts_with(trim_and_lowercase($line), "doomednums")) {
                Logger::pg("Found doomednums");
                $parsed_data['doomednums'] = $this->parseNumberSection();
                continue;
            }
            
            if(str_starts_with(trim_and_lowercase($line), "spawnnums")) {
                Logger::pg("Found spawnnums");
                $parsed_data['spawnnums'] = $this->parseNumberSection();
                continue;
            }

            if (trim_and_lowercase($line) == 'nojump' || trim_and_lowercase($line) == 'nocrouch') {
                $parsed_data['jumpcrouch'] = 0;
            }
            if (trim_and_lowercase($line) == 'allowjump' || trim_and_lowercase($line) == 'allowcrouch') {
                $parsed_data['jumpcrouch'] = 1;
            }
            
            $line_tokens = explode("=", $line);

            $key = trim_and_lowercase($line_tokens[0]);
            if (in_array($key, get_setting("PROJECT_ALLOWED_MAPINFO_PROPERTIES")) || $key == 'music') {
                if (!isset($line_tokens[1])) {
                    $value = "_SET_";
                } else {
                    $value = trim_and_lowercase($line_tokens[1]);
                }
                $parsed_data[$key] = $value;
            }
            
            if (in_array($key, ['sky1', 'skybox'])) {
                $value = strip_quotes($line_tokens[1]);
                $terminateChar = min(strpos($value, ","), strpos($value, " "));
                if ($terminateChar) {
                    $value = trim(substr($value, 0, $terminateChar));
                }
                $parsed_data['sky1'] = $value;
            }
            if ($key == 'sky2') {
                $value = strip_quotes($line_tokens[1]);
                $terminateChar = min(strpos($value, ","), strpos($value, " "));
                if ($terminateChar) {
                    $value = trim(substr($value, 0, $terminateChar));
                }
                $parsed_data['sky2'] = $value;
            }
            
            $this->current_line_number++;
        }
        
        return($parsed_data);
    }
    
    public function parseNumberSection(): array
    {
        $parsedNumbers = [];
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
            $number = trim_and_lowercase($line_tokens[0]);
            $classname = strip_quotes(trim_and_lowercase($line_tokens[1]));
            if (!is_numeric($number)) {
                $this->current_line_number++;
                continue;
            }
            $parsedNumbers[$number] = $classname;
            $this->current_line_number++;
        }
        return $parsedNumbers;
    }
}
