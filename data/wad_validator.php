<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_functions.php');

class Wad_Validator {

    private string $filename;
    
    public function __construct($filename) {
        $this->filename = $filename;
    }
    
    function validate() {    

    }
    
    function handle_validation_failure() {
        die();
    }
}