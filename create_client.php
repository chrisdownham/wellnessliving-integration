<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Wl\Lead\LeadModel;
use WellnessLiving\WlRegionSid;

// Helper to send JSON and exit
function send_json_response(array $data, int $code = 200) {
  header('Content-Type: application/json', true, $code);
  echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  send_json_response(['status'=>'error','message'=>'Use POST'], 405);
}

// Pull in your five form fields
$first = $_POST['s_first_name']    ?? null;
$last  = $_POST['s_last_name']     ?? null;
$email = $_POST['s_email']         ?? null;
$phone = $_POST['s_phone']         ?? null;
$home  = $_POST['s_home_location'] ?? null;

// Validate all five
if (! $first || ! $last || ! $email || ! $phone || ! $home) {
  send_json_response([
    'status'=>'error',
    'message'=>'Missing one of: s_first_name, s_last_name, s_email, s_phone, s_home_location'
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

  // 2) Fetch the “new-client” fields
  $lead = new LeadModel($cfg);
  $lead->cookieSet($notepad->cookieGet());
  $lead->k_business = $_ENV['WL_BUSINESS_ID'];
  $lead->get();

  // 3) Build the payload, mapping by id_field_general:
  //    2 = first name, 1 = last name, 3 = email, 4 = cell phone, 5 = home location
  $payload = [];
  foreach ($lead->a_field_list as $f) {
    switch ($f['id_field_general']) {
      case 2:
        $payload[ $f['k_field'] ] = $first;
        break;
      case 1:
        $payload[ $f['k_field'] ] = $last;
        break;
      case 3:
        $payload[ $f['k_field'] ] = $email;
        break;
      case 4:
        $payload[ $f['k_field'] ] = $phone;
        break;
      case 5:
        $payload[ $f['k_field'] ] = $home;
        break;
    }
  }

  // 4) Create the client
  $lead->a_field_data = $payload;
  $lead->post();

  // 5) Return success
  send_json_response([
    'status'         => 'success',
    'new_client_uid' => $lead->uid
  ], 201);

} catch (\Exception $e) {
  // 6) Return error
  send_json_response([
    'status'  => 'error',
    'message' => 'API error: ' . $e->getMessage()
  ], 500);
}