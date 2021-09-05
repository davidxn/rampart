<?php

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/catalog_handler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'scripts/auth.php');


class Map_Data_Editor {
    
    function update_field($pin, $field, $value) {
        if (!$pin) {
            echo(json_encode(['success' => false, 'error' => 'No PIN provided']));
            die();
        }
        
        if ($field != 'mapinfo') {
            $value = $this->clean_text($value);
        }
        if (!$value) {
            echo(json_encode(['success' => false, 'error' => 'No value provided']));
            die();
        }

        $catalog_handler = new Catalog_Handler();
        if (in_array($field, ['lumpname', 'map_name', 'author', 'mapinfo'])) {
            $catalog_handler->update_map_property($pin, $field, $value);
            echo(json_encode(['success' => true]));
            die();
        }
        else if ($field == 'pin') {
            $success = $catalog_handler->change_pin($pin, $value);
            if (!$success) {
                echo(json_encode(['success' => false, 'error' => 'Already a map with this PIN']));
                die();
            }
            echo(json_encode(['success' => true]));
            die();
        }
        else if ($field == 'lock') {
            if ($value > 0) {
                $catalog_handler->lock_map($pin);
            } else {
                $catalog_handler->unlock_map($pin);
            }
            echo(json_encode(['success' => true]));
            die();
        }
        echo(json_encode(['success' => false, 'error' => 'Not a supported field']));
    }

    function clean_text($string, $length = 0) {
       $string = trim($string);
       $string = preg_replace('/[^A-Za-z0-9\-\'! ]/', '', $string); // Removes special chars.
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }
}

$pin = $_POST['pin'];
$field = $_POST['field'];
$value = $_POST['value'];

$handler = new Map_Data_Editor();
$handler->update_field($pin, $field, $value);
