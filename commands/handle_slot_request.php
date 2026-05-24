<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

$catalog = new Catalog_Handler();
foreach ($catalog->get_catalog() as $rampId => $rampMap) {
    if (count($rampMap->flags) == 0) {
        // Set some random flags!
        foreach ([RampMap::FLAG_JUMP, RampMap::FLAG_PEACE, RampMap::FLAG_GAME, RampMap::FLAG_MOUSELOOK,
                     RampMap::FLAG_NEW_MONSTERS, RampMap::FLAG_NEW_WEAPONS, RampMap::FLAG_PUZZLE,
            RampMap::FLAG_SCARE, RampMap::FLAG_SLAUGHTER, RampMap::FLAG_SPIDER, RampMap::FLAG_WATER, RampMap::FLAG_WIP] as $flag) {
            if (rand(0, 5) == 1) {
                $rampMap->flags[] = $flag;
            }
        }
    }
    $catalog->update_map_property($rampId, "flags", $rampMap->flags);
}