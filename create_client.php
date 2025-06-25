<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Wl\Lead\LeadModel;
use WellnessLiving\WlRegionSid;

// JSON helper
function send_json_response(array $data, int $code = 200) {
  header('Content-Type: application/json', true, $code);
  echo json_encode($data);
  exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  send_json_response(['status'=>'error','message'=>'Use POST'], 405);
}

// Pull in the four lead fields
$first = $_POST['s_first_name'] ?? null;
$last  = $_POST['s_last_name']  ?? null;
$email = $_POST['s_email']      ?? null;
$phone = $_POST['s_phone']      ?? null;

// Validate required four
if (! $first || ! $last || ! $email || ! $phone) {
  send_json_response([
    'status'=>'error',
    'message'=>'Missing required fields: s_first_name, s_last_name, s_email, s_phone'
  ], 422);
}

try {
  // 1) Authenticate
  $cfg     = ExampleConfig::create(WlRegionSid::US_EAST_1);
  $notepad = new NotepadModel($cfg);
  $notepad->get();

  $enter = new EnterModel($cfg);
  $enter->cookieSet($notepad->cookieGet());
  $enter->s_login    = $_ENV['WL_LOGIN'];
  $enter->s_notepad  = $notepad->s_notepad;
  $enter->s_password = $notepad->hash($_ENV['WL_PASSWORD']);
  $enter->post();

  // 2) Load the “new-client” fields
  $lead = new LeadModel($cfg);
  $lead->cookieSet($notepad->cookieGet());
  $lead->k_business = $_ENV['WL_BUSINESS_ID'];
  $lead->get();

  // 3) Build payload for exactly those four
  $payload = [];
  foreach ($lead->a_field_list as $f) {
    switch ($f['id_field_general']) {
      case 2:  // First name
        $payload[$f['k_field']] = $first;
        break;
      case 1:  // Last name
        $payload[$f['k_field']] = $last;
        break;
      case 3:  // Email/Username
        $payload[$f['k_field']] = $email;
        break;
      case 4:  // Cell phone
        $payload[$f['k_field']] = $phone;
        break;
    }
  }

  // 4) Create the lead (client)
  $lead->a_field_data = $payload;
  $lead->post();

  // 5) Success
  send_json_response([
    'status'         => 'success',
    'new_client_uid' => $lead->uid
  ], 201);

} catch (\Exception $e) {
  send_json_response([
    'status'  => 'error',
    'message' => 'API error: '.$e->getMessage()
  ], 500);
}