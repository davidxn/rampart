<?php

set_time_limit(600);

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');

$redirect = $_GET['redirect'] ?? false;
$nocache = $_GET['nocache'] ?? false;
$nozip = $_GET['nozip'] ?? false;

$lock_file_recent = file_exists(LOCK_FILE_COMPILE) && (time() - get_mtime(LOCK_FILE_COMPILE)) < 600;
$pk3_is_current = true;

if ($lock_file_recent) {
    echo json_encode(['success' => false, 'error' => 'Project is already being generated! Hold on a minute then try again']);
    die();
}

// Only perform PK3 check if we're not forcing a rebuild

if ($nocache) {
    Logger::lg("Nocache flag was passed to PK3 updater - will rebuild");
    $pk3_is_current = false;
}

// Check for changes to the catalog file
if (get_mtime(CATALOG_FILE) > get_mtime(get_project_full_path())) {
    $pk3_is_current = false;
    Logger::lg("Catalog file has newer data than latest snapshot - will rebuild");
}

// Check for changes to the settings file
if (get_mtime(SETTINGS_FILE) > get_mtime(get_project_full_path())) {
    $pk3_is_current = false;
    Logger::lg("Settings file has been changed since latest snapshot - will rebuild");
}

// Now check the fixedcontent folder... if we don't have a script, just keep the answer from before
if (!empty($GLOBALS["STATIC_CONTENT_MTIME_SCRIPT"])) {
    @$static_content_mtime = trim(shell_exec($GLOBALS["STATIC_CONTENT_MTIME_SCRIPT"]));
    if (is_numeric($static_content_mtime) && !empty($static_content_mtime) && $static_content_mtime > get_mtime(get_project_full_path())) {
        Logger::lg("Static content folder has newer data than latest snapshot - will rebuild");
        $pk3_is_current = false;
    }
}

// If we don't have a project file at all, we have to build
if (!file_exists(get_project_full_path())) {
    Logger::lg("No snapshot exists - will rebuild");
    $pk3_is_current = false;
}

// If we've reached here and the pk3_is_current flag is still true, then we've decided the existing PK3 is still OK
if ($pk3_is_current) {
    Logger::lg("PK3 passed all checks - serving the existing one");
    echo json_encode(['success' => true, 'newpk3' => false]);
    die();
}

// Trigger a rebuild
try {
    $handler = new Project_Compiler();
    if ($handler->compile_project(!$nozip)) {
        if ($redirect) {
            header("Location: admin/index.php");
            die();
        }
        echo json_encode(['success' => true, 'newpk3' => true]);
        die();
    }
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Something went wrong, the project admin should be able to see exactly what']);
}