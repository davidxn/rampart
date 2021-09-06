<?php

set_time_limit(600);

require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "_constants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/logger.php");
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "scripts/project_compiler.php");

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : false;
$nocache = isset($_GET['nocache']) ? $_GET['nocache'] : false;

$lock_file_recent = file_exists(LOCK_FILE_COMPILE) && (time() - filemtime(LOCK_FILE_COMPILE)) < 600;
$pk3_is_current = file_exists(get_project_full_path()) && (filemtime(CATALOG_FILE) < filemtime(get_project_full_path()));

if ($lock_file_recent) {
    echo json_encode(['success' => false, 'error' => 'Project is already being generated! Hold on a minute then try again']);
    die();
}
if (!$pk3_is_current) {
    Logger::lg("Catalog is newer than latest build, so an update is required: " . (filemtime(CATALOG_FILE) - filemtime(get_project_full_path())));
} else {
    if (file_exists(get_project_full_path())) {
        Logger::lg("Catalog is older than latest build - will serve existing one: " . (filemtime(CATALOG_FILE) - filemtime(get_project_full_path())));
    } else {
        Logger::lg("No snapshot exists - will create one");
    }
}

//If we already have a PK3 that's been generated after the last update of the catalogue file, terminate and instruct to serve the old one
if (!$nocache && ($lock_file_recent || $pk3_is_current)) {
    echo json_encode(['success' => true, 'newpk3' => false]);
    die();
}

$handler = new Project_Compiler();
if ($handler->compile_project()) {
    if ($redirect) {
        header("Location: admin/index.php");
        die();
    }
    echo json_encode(['success' => true, 'newpk3' => true]);
    die();
}
echo json_encode(['success' => false, 'error' => 'Something went wrong, the project admin should be able to see exactly what']);
