<?php
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '_bootstrap.php');
require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'includes/classes/pin_managers.php');

sleep(2);
$email = $_POST['email'] ?? '';
$email_parts = explode("@", $email);
if (count($email_parts) != 2 || $email_parts[0] == '' || $email_parts[1] == '' || !str_contains($email_parts[1], '.')) {
    echo json_encode(['success' => false, 'error' => 'That isn\'t an email address!']);
    die();
}

if (is_ip_banned()) {
    echo json_encode(['success' => true]);
    die();
}

$pin_manager = get_setting("PIN_MANAGER_CLASS");
$pin = (new $pin_manager())->get_new_provisional_pin($email);

// TODO Email this, don't return it!
echo(json_encode(['success' => true]));