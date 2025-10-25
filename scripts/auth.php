<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_constants.php');
$authfailstring = '<center><img src="/img/authfail.png"></img></center>';
$password = '';
require_once(PASSWORD_FILE);

if (!empty($password)) {
    if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="RAMPART"');
        header('HTTP/1.0 401 Unauthorized');
        //HTTP auth in browser happens here...
        require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
        echo $authfailstring;
        exit;
    }

    if ($_SERVER['PHP_AUTH_PW'] != $password) {
        header('WWW-Authenticate: Basic realm="RAMPART"');
        header('HTTP/1.0 401 Unauthorized');
        require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'header.php');
        echo $authfailstring;
        exit;
    }
}
