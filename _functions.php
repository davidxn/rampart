<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');

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

function get_safe_lump_file_name($name) {
    return str_replace("\\", "^", $name);
}

function html_radio_button($setting, $text, $value = null, $linebreak = false, $default = false) {
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

function get_error_link($key, $args = []) {
    $error_string = COMPILE_ERRORS[$key] ?? "[Could not find error string]";
    $i = 1;
    foreach ($args as $arg) {
        $error_string = str_replace('$' . $i, $arg, $error_string);
        $i++;
    }
    $emoji = str_starts_with($key, 'ERR') ? '❌' : '⚠';
    return sprintf('<a name="%s"><a href="/errors.php#%s">%s %s</a>', $key, $key, $emoji, $error_string);
}