<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

// JSON helper
function send_json_response(array $data, int $code = 200) {
  header('Content-Type: application/json', true, $code);
  echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  send_json_response(['status'=>'error','message'=>'Use POST'], 405);
}

// Grab your form fields
$first = $_POST['s_first_name']    ?? null;
$last  = $_POST['s_last_name']     ?? null;
$email = $_POST['s_email']         ?? null;
$phone = $_POST['s_phone']         ?? null;
$home  = $_POST['s_home_location'] ?? null;

// Validate required
if (! $first || ! $last || ! $email || ! $phone || ! $home) {
  send_json_response([
    'status'=>'error',
    'message'=>'Missing one of: s_first_name, s_last_name, s_email, s_phone, s_home_location'
  ], 422);
}

try {
  // 1) Authenticate with WL
  $cfg     = ExampleConfig::create(WlRegionSid::US_EAST_1);
  $notepad = new NotepadModel($cfg);
  $notepad->get();

  $enter = new EnterModel($cfg);
  $enter->cookieSet($notepad->cookieGet());
  $enter->s_login    = $_ENV['WL_LOGIN'];
  $enter->s_notepad  = $notepad->s_notepad;
  $enter->s_password = $notepad->hash($_ENV['WL_PASSWORD']);
  $enter->post();

  // 2) Build the a_change payload
  //    These keys must match the SDK's field names for your business
  $changes = [
    's_first_name'    => ['s_value' => $first],
    's_last_name'     => ['s_value' => $last],
    's_email'         => ['s_value' => $email],
    's_phone'         => ['s_value' => $phone],
    's_home_location' => ['s_value' => $home],
  ];

  // 3) Create the client
  $profile = new EditModel($cfg);
  $profile->cookieSet($notepad->cookieGet());
  $profile->a_change   = $changes;
  $profile->k_business = $_ENV['WL_BUSINESS_ID'];

  $result = $profile->post();

  // 4) Success!
  send_json_response([
    'status'         => 'success',
    'new_client_uid' => $result['uid'] ?? null
  ], 201);

} catch (\GuzzleHttp\Exception\RequestException $g) {
  // If the API returned a 4xx/5xx with a body, show it
  $resp = $g->hasResponse()
        ? (string)$g->getResponse()->getBody()
        : $g->getMessage();
  $code = $g->hasResponse()
        ? $g->getResponse()->getStatusCode()
        : 500;
  send_json_response([
    'status'  => 'error',
    'message' => 'API error',
    'detail'  => $resp
  ], $code);

} catch (\Exception $e) {
  send_json_response([
    'status'  => 'error',
    'message' => 'Unexpected error: '.$e->getMessage()
  ], 500);
}