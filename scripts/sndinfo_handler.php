<?php
require_once("_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");

class Sndinfo_Handler {
    
    private $current_line_number = 0;
    private $input_lines = [];
    private $requested_sound_lump_names = [];
    
    public function __construct($bytes) {
        $this->input_lines = split(PHP_EOL, $bytes);
    }
    
    public function parse() {
        while ($this->current_line_number < count($this->input_lines)) {
            $current_line = $this->input_lines[$this->current_line_number];
            $tokens = preg_split('/\s+/', trim($current_line));
            $is_command = substr($tokens[0], 0, 1) == "$";
            $is_comment = substr($tokens[0], 0, 2) == "//";
            if (count($tokens) == 2 && !$is_command && !$is_comment) {
                //We're going to assume this is a sound name followed by a lump name. Add the lump name to our requested sounds.
                $this->requested_sound_lump_names[] = strtoupper($tokens[1]);
                Logger::pg("Added " . strtoupper($tokens[1]) . " to requested sound lump names");
            }
            $this->current_line_number++;
        }
        return [$this->input_lines, $this->requested_sound_lump_names];
    }
}