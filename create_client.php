<?php
// (1) Autoload & config
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Lead\LeadModel;
use WellnessLiving\WlRegionSid;
// ← correct import for the field‐ID constants:
use WellnessLiving\Wl\Field\WlFieldGeneralSid;

// JSON helper
function send_json_response($data, $code = 200) {
  header('Content-Type: application/json', true, $code);
  echo json_encode($data);
  exit;
}

// (2) Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  send_json_response(['status'=>'error','message'=>'Use POST'], 405);
}

// (3) Read form fields
$first = $_POST['s_first_name'] ?? null;
$last  = $_POST['s_last_name']  ?? null;
$email = $_POST['s_email']      ?? null;

if (!$first || !$last || !$email) {
  send_json_response([
    'status'=>'error',
    'message'=>'Missing s_first_name, s_last_name or s_email.'
  ], 422);
}

try {
  // (4) Authenticate with WellnessLiving
  $o_config  = ExampleConfig::create(WlRegionSid::US_EAST_1);
  $o_notepad = new NotepadModel($o_config);
  $o_notepad->get();

  $o_enter = new EnterModel($o_config);
  $o_enter->cookieSet($o_notepad->cookieGet());
  $o_enter->s_login    = $_ENV['WL_LOGIN'];
  $o_enter->s_notepad  = $o_notepad->s_notepad;
  $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']);
  $o_enter->post();

  // (5) Fetch the “new client” field list
  $o_lead = new LeadModel($o_config);
  $o_lead->cookieSet($o_notepad->cookieGet());
  $o_lead->k_business = $_ENV['WL_BUSINESS_ID'];
  $o_lead->get();

  // Build payload mapping generic field IDs to your values
  $payload = [];
  foreach ($o_lead->a_field_list as $f) {
    switch ($f['id_field_general']) {
      case WlFieldGeneralSid::NAME_FIRST:
        $payload[ $f['k_field'] ] = $first;
        break;
      case WlFieldGeneralSid::NAME_LAST:
        $payload[ $f['k_field'] ] = $last;
        break;
      case WlFieldGeneralSid::LOGIN:
        $payload[ $f['k_field'] ] = $email;
        break;
      // add more cases if you need phone, etc.
    }
  }

  // (6) Create the client
  $o_lead->a_field_data = $payload;
  $o_lead->post();

  send_json_response([
    'status'         => 'success',
    'new_client_uid' => $o_lead->uid
  ], 201);

} catch (\Exception $e) {
  send_json_response([
    'status'=>'error',
    'message'=>'API error: '.$e->getMessage()
  ], 500);
}
