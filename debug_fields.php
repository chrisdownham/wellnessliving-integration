<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Wl\Lead\LeadModel;
use WellnessLiving\WlRegionSid;

// 1. Authenticate
$config  = ExampleConfig::create(WlRegionSid::US_EAST_1);
$notepad = new NotepadModel($config);
$notepad->get();

$enter = new EnterModel($config);
$enter->cookieSet($notepad->cookieGet());
$enter->s_login    = $_ENV['WL_LOGIN'];
$enter->s_notepad  = $notepad->s_notepad;
$enter->s_password = $notepad->hash($_ENV['WL_PASSWORD']);
$enter->post();

// 2. Fetch the “new client” fields
$lead = new LeadModel($config);
$lead->cookieSet($notepad->cookieGet());
$lead->k_business = $_ENV['WL_BUSINESS_ID'];
$lead->get();

// 3. Output as pure JSON
header('Content-Type: application/json', true, 200);
echo json_encode($lead->a_field_list);
