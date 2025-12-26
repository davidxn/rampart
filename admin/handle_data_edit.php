<?php
$GLOBALS['auth'] = true;
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

class Map_Data_Editor {
    
    function update_field($rampId, $field, $value): void {
        if (!$rampId) {
            echo(json_encode(['success' => false, 'error' => 'No Ramp map ID provided']));
            die();
        }
        
        if ($field != 'mapInfoString') {
            $value = $this->clean_text($value);
            if (!$value) {
                echo(json_encode(['success' => false, 'error' => 'No value provided']));
                die();
            }
        }

        $catalog_handler = new Catalog_Handler();
        if (in_array($field, ['lump', 'name', 'author', 'mapInfoString', 'mapnum', 'disabled'])) {
            $catalog_handler->update_map_property($rampId, $field, $value);
            echo(json_encode(['success' => true]));
            die();
        }
        else if ($field == 'pin') {
            $success = $catalog_handler->change_pin($rampId, $value);
            if (!$success) {
                echo(json_encode(['success' => false, 'error' => 'Already a map with this PIN']));
                die();
            }
            echo(json_encode(['success' => true]));
            die();
        }
        else if ($field == 'lock') {
            if ($value > 0) {
                $catalog_handler->lock_map($rampId);
            } else {
                $catalog_handler->unlock_map($rampId);
            }
            echo(json_encode(['success' => true]));
            die();
        }
        echo(json_encode(['success' => false, 'error' => 'Not a supported field']));
    }

    function clean_text($string, $length = 0): string {
       $string = trim($string);
       $string = preg_replace('/[^A-Za-z0-9\-\'! ]/', '', $string); // Removes special chars.
       if ($length) {
           $string = substr($string, 0, $length);
       }
       return $string;
    }
}

$rampId = $_POST['rampid'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

$handler = new Map_Data_Editor();
$handler->update_field($rampId, $field, $value);
