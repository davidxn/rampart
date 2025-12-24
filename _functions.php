<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');

function get_source_wad_file_name($slot): string {
    // Prefix under 10 with a 0
    if ($slot >= 0 && $slot < 10) {
        return ("RM" . (substr("0" . $slot, -2)) . ".WAD");
    }
    return "RM" . $slot . ".WAD";
}

function get_mtime($file): string {
    if (is_file($file)) {
        return filemtime($file);
    }
    return 0;
}

function get_safe_lump_file_name($name): string {
    return str_replace("\\", "^", $name);
}

function compare_lumpnamed_things($a, $b): int {
    if (str_starts_with($a['lumpname'], "MAP") && str_starts_with($b['lumpname'], "MAP")) {
        return intval(substr($a['lumpname'], 3)) <=> intval(substr($b['lumpname'], 3));
    }
    return $a['lumpname'] <=> $b['lumpname'];
}

function html_radio_button($setting, $text, $value = null, $linebreak = false, $default = false): string {
    $html = "";
    
    if ($linebreak) {
        $html .= "<div style='padding-bottom: 4px'>";
    }
    if ($value === null) {
        $value = $text;
    }
    $id = $setting . "__" . strtoupper(str_replace(" ", "", $value));
    $html .= '<input type="radio" id="'.$id.'"' . PHP_EOL
            . 'name="'.$setting.'" value="'.$value.'"';
    if ($default === false) { $default = get_setting($setting); }

    $html .= ($default == $value ? " checked=\"checked\"" : "") . '>' . PHP_EOL;
    $html .= '<label for="'.$id.'">'.$text.'</label>' . PHP_EOL;
    
    if ($linebreak) { $html .= "</div>"; }
    
    return $html;
}

function wait_for_upload_lock(): bool {
    $tries = 0;

    while (file_exists(LOCK_FILE_UPLOAD) && (time() - filemtime(LOCK_FILE_UPLOAD)) < 60) {
        sleep(1);
        if ($tries > 10) {
            Logger::lg("Couldn't acquire upload lock");
            return false;
        }
        $tries++;
    }
    file_put_contents(LOCK_FILE_UPLOAD, ":)");
    Logger::lg("Upload lock acquired");
    return true;
}

function get_error_link($key, $args = []): string {
    $error_string = COMPILE_ERRORS[$key] ?? "[Could not find error string]";
    $i = 1;
    foreach ($args as $arg) {
        $error_string = str_replace('$' . $i, $arg, $error_string);
        $i++;
    }
    $emoji = str_starts_with($key, 'ERR') ? '❌' : '⚠';
    return sprintf('<a name="%s"><a href="/errors.php#%s">%s %s</a>', $key, $key, $emoji, $error_string);
}