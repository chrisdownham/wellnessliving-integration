<?php

// This MUST be the first line to load all necessary SDK classes.
require_once 'vendor/autoload.php';

// This securely loads variables from the .env file if it exists (for local testing).
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// All the necessary classes should now be findable.
use WellnessLiving\Config\WlConfigDeveloper;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Business\BusinessModel; // The tool for getting business details
use WellnessLiving\WlRegionSid;

class MyConfig extends WlConfigDeveloper
{
  const AUTHORIZE_CODE = 'eBYCKvZ90FdqbLoRTb44tWOARpuZPLBaFphaSUZMOTh2';
  const AUTHORIZE_ID = 'raa-EClh0AuHE8XbHWEO';
}

try {
    echo "Attempting to fetch business details...\n";

    $o_config = MyConfig::create(WlRegionSid::US_EAST_1);
    
    $o_notepad = new NotepadModel($o_config);
    $o_notepad->get();

    $o_enter = new EnterModel($o_config);
    $o_enter->cookieSet($o_notepad->cookieGet());
    $o_enter->s_login = $_ENV['WL_LOGIN'];
    $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']);
    $o_enter->s_notepad = $o_notepad->s_notepad;
    $o_enter->post();

    // Use the BusinessModel to get details.
    $o_business = new BusinessModel($o_config);
    $o_business->k_business = $_ENV['WL_BUSINESS_ID'];
    $o_business->get(); // This makes the API call.

    // Get the data from the successful call.
    $a_result = $o_business->dataGet();

    echo "✅ Success! API call was successful.\n";
    echo "Business Name: " . ($a_result['s_title'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "❌ Error: An API error occurred: " . $e->getMessage() . "\n";
}
?>