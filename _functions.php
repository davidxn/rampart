<?php
function get_source_wad_file_name($slot) {
    return ("RM" . (substr("0" . $slot, -2)) . ".WAD");
}

function get_mtime($file) {
    if (is_file($file)) {
        return filemtime($file);
    }
    return 0;
}
