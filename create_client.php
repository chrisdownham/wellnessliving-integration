<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

function send_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['status' => 'error', 'message' => 'This endpoint only accepts POST requests.']);
}

$s_first_name = $_POST['s_first_name'] ?? null;
$s_last_name  = $_POST['s_last_name']  ?? null;
$s_email      = $_POST['s_email']      ?? null;

if (!$s_first_name || !$s_last_name || !$s_email) {
    send_json_response(['status' => 'error', 'message' => 'Missing required fields: s_first_name, s_last_name, s_email.']);
}

try {
    $o_config  = ExampleConfig::create(WlRegionSid::US_EAST_1);
    $o_notepad = new NotepadModel($o_config);
    $o_notepad->get();

    $o_enter           = new EnterModel($o_config);
    $o_enter->cookieSet($o_notepad->cookieGet());
    $o_enter->s_login    = $_ENV['WL_LOGIN'];
    $o_enter->s_notepad  = $o_notepad->s_notepad;
    $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']);
    $o_enter->post();

    $o_profile         = new EditModel($o_config);
    $o_profile->a_change = [
        's_first_name' => ['s_value' => $s_first_name],
        's_last_name'  => ['s_value' => $s_last_name],
        's_email'      => ['s_value' => $s_email]
    ];
    $o_profile->k_business = $_ENV['WL_BUSINESS_ID'];

    $a_result = $o_profile->post();

    send_json_response([
        'status'         => 'success',
        'message'        => 'Client created successfully in WellnessLiving.',
        'new_client_uid' => $a_result['uid'] ?? null
    ]);

} catch (Exception $e) {
    send_json_response([
        'status'  => 'error',
        'message' => 'An API error occurred: ' . $e->getMessage()
    ]);
}