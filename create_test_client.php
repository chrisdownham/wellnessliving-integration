<?php

// Use Composer's autoloader first.
require_once 'vendor/autoload.php';
// MANUALLY include the WellnessLiving SDK's autoloader as a fallback.
require_once 'WellnessLiving/wl-autoloader.php';

// This securely loads variables from the .env file if it exists (for local testing).
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// All the necessary classes should now be findable.
use WellnessLiving\Config\WlConfigDeveloper;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

class MyConfig extends WlConfigDeveloper
{
  const AUTHORIZE_CODE = 'eBYCKvZ90FdqbLoRTb44tWOARpuZPLBaFphaSUZMOTh2';
  const AUTHORIZE_ID = 'raa-EClh0AuHE8XbHWEO';
}

try {
    echo "Attempting to create a test client...\n";

    $o_config = MyConfig::create(WlRegionSid::US_EAST_1);
    
    $o_notepad = new NotepadModel($o_config);
    $o_notepad->get();

    $o_enter = new EnterModel($o_config);
    $o_enter->cookieSet($o_notepad->cookieGet());
    $o_enter->s_login = $_ENV['WL_LOGIN'];
    $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']);
    $o_enter->s_notepad = $o_notepad->s_notepad;
    $o_enter->post();

    $o_profile = new EditModel($o_config);
    $a_change = [
        's_first_name' => ['s_value' => 'FinalRailwayClient'],
        's_last_name'  => ['s_value' => 'Test'],
        's_email'      => ['s_value' => 'final-railway-client-' . time() . '@example.com']
    ];
    
    $o_profile->a_change = $a_change;
    $o_profile->k_business = $_ENV['WL_BUSINESS_ID'];

    $a_result = $o_profile->post();

    echo "✅ Success! Client created successfully in WellnessLiving.\n";
    echo "New Client UID: " . ($a_result['uid'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "❌ Error: An API error occurred: " . $e->getMessage() . "\n";
}
?>