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

// Pull in your five fields
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

  // 2) Load the “new client” fields
  $lead = new LeadModel($cfg);
  $lead->cookieSet($notepad->cookieGet());
  $lead->k_business = $_ENV['WL_BUSINESS_ID'];
  $lead->get();

  // 3) Map each required value by its id_field_general
  $payload = [];
  foreach ($lead->a_field_list as $f) {
    switch ($f['id_field_general']) {
      case 2:  // First name
        $payload[ $f['k_field'] ] = $first;
        break;
      case 1:  // Last name
        $payload[ $f['k_field'] ] = $last;
        break;
      case 3:  // Email/Username
        $payload[ $f['k_field'] ] = $email;
        break;
      case 4:  // Cell phone
        $payload[ $f['k_field'] ] = $phone;
        break;
      case 5:  // Home location
        $payload[ $f['k_field'] ] = $home;
        break;
    }
  }

  // 4) Create the client
  $lead->a_field_data = $payload;
  $lead->post();

  // 5) Success
  send_json_response([
    'status'         => 'success',
    'new_client_uid' => $lead->uid
  ], 201);

} catch (\Exception $e) {
  // 6) Forward any API error
  send_json_response([
    'status'=>'error',
    'message'=>'API error: '.$e->getMessage()
  ], 500);
}