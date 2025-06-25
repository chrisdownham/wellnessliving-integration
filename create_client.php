<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Lead\LeadModel;
use WellnessLiving\WlRegionSid;
use WellnessLiving\Wl\Field\WlFieldGeneralSid;

function send_json_response($data, $code = 200) {
  header('Content-Type: application/json', true, $code);
  echo json_encode($data);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  send_json_response(['status'=>'error','message'=>'Use POST'], 405);
}

$first = $_POST['s_first_name'] ?? null;
$last  = $_POST['s_last_name']  ?? null;
$email = $_POST['s_email']      ?? null;
$phone = $_POST['s_phone']      ?? null;

if (!$first || !$last || !$email || !$phone) {
  send_json_response([
    'status'=>'error',
    'message'=>'Missing one of required fields: s_first_name, s_last_name, s_email, s_phone.'
  ], 422);
}

try {
  // Authenticate
  \$cfg     = ExampleConfig::create(WlRegionSid::US_EAST_1);
  \$notepad = new NotepadModel(\$cfg);
  \$notepad->get();

  \$enter   = new EnterModel(\$cfg);
  \$enter->cookieSet(\$notepad->cookieGet());
  \$enter->s_login    = \$_ENV['WL_LOGIN'];
  \$enter->s_notepad  = \$notepad->s_notepad;
  \$enter->s_password = \$notepad->hash(\$_ENV['WL_PASSWORD']);
  \$enter->post();

  // Fetch the “new client” field list
  \$lead = new LeadModel(\$cfg);
  \$lead->cookieSet(\$notepad->cookieGet());
  \$lead->k_business = \$_ENV['WL_BUSINESS_ID'];
  \$lead->get();

  // Build payload
  \$payload = [];
  foreach (\$lead->a_field_list as \$f) {
    switch (\$f['id_field_general']) {
      case WlFieldGeneralSid::NAME_FIRST:
        \$payload[\$f['k_field']] = \$first;
        break;
      case WlFieldGeneralSid::NAME_LAST:
        \$payload[\$f['k_field']] = \$last;
        break;
      case WlFieldGeneralSid::LOGIN:
        \$payload[\$f['k_field']] = \$email;
        break;
      case 4:  // id_field_general=4 is “Cell phone”
        \$payload[\$f['k_field']] = \$phone;
        break;
    }
  }

  // Create the client
  \$lead->a_field_data = \$payload;
  \$lead->post();

  send_json_response([
    'status'         => 'success',
    'new_client_uid' => \$lead->uid
  ], 201);

} catch (\Exception \$e) {
  send_json_response([
    'status'=>'error',
    'message'=>'API error: '.\$e->getMessage()
  ], 500);
}
