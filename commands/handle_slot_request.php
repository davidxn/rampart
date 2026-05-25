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

$api_key = file_get_contents(WORK_FOLDER . "mailtrap.secret");
if (!$api_key) {
    echo json_encode(['success' => true, 'error' => 'PIN was created but failed to find mailtrap API key!']);
}

$url = 'https://send.api.mailtrap.io/api/send';

$data = [
    'from' => [
        'email' => 'pinmanager@doomproject.com',
        'name' => 'RAMP 2026 PIN manager',
    ],
    'to' => [
        [
            'email' => $email,
        ]
    ],
    'template_uuid' => "78e6d49f-64cf-4a8a-8ca2-7d69b7069919",
    'template_variables' => [
        'pin' => $pin,
    ],
];

$headers = [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json',
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => "cURL Error: " . curl_error($ch)]);
    die();
}

echo(json_encode(['success' => true]));
