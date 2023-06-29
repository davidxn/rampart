<?php
require_once("_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

class Sndinfo_Handler {
    
    private $current_line_number = 0;
    private $input_lines = [];
    private $requested_sound_lump_names = [];
    private $requested_sound_definitions = [];
    
    public function __construct($bytes) {
        $this->input_lines = split(PHP_EOL, $bytes);
    }
    
    public function parse() {
        while ($this->current_line_number < count($this->input_lines)) {
            $current_line = $this->input_lines[$this->current_line_number];
            $tokens = preg_split('/\s+/', trim($current_line));
            $is_command = substr($tokens[0], 0, 1) == "$";
            $is_comment = substr($tokens[0], 0, 2) == "//";
            $is_two_token_line = (count($tokens) == 2);
            $is_three_token_line = (count($tokens) == 3 && trim($tokens[1]) == "=");
            
            if (($is_two_token_line || $is_three_token_line) && !$is_command && !$is_comment) {
                //Just copy the sound lump name into the middle place if we had three tokens
                if ($is_three_token_line) {
                    $tokens[1] = $tokens[2];
                }
                //We're going to assume this is a sound name followed by a lump name. Add the lump name to our requested sounds.
                $this->requested_sound_definitions[] = strtolower($tokens[0]);
                $this->requested_sound_lump_names[] = str_replace("\"", "", strtoupper($tokens[1]));
                Logger::pg("Added " . strtolower($tokens[0]) . " to requested sound definitions");
                Logger::pg("Added " . strtoupper($tokens[1]) . " to requested sound lump names");
            }
            $this->current_line_number++;
        }
        return ['input_lines' => $this->input_lines, 'requested_lump_names' => $this->requested_sound_lump_names, 'requested_definitions' => $this->requested_sound_definitions];
    }
}