<?php
function get_source_wad_file_name($slot) {
    // Prefix under 10 with a 0
    if ($slot >= 0 && $slot < 10) {
        return ("RM" . (substr("0" . $slot, -2)) . ".WAD");
    }
    return "RM" . $slot . ".WAD";
}

function get_mtime($file) {
    if (is_file($file)) {
        return filemtime($file);
    }
    return 0;
}
