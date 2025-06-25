<?php

// Use Composer's autoloader for all classes. This MUST be the first line.
require_once 'vendor/autoload.php';

// This securely loads variables from the .env file if it exists (for local use).
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * The custom configuration class, now defined directly inside our script.
 */
class MyConfig extends \WellnessLiving\Config\WlConfigDeveloper
{
  const AUTHORIZE_CODE = 'eBYCKvZ90FdqbLoRTb44tWOARpuZPLBaFphaSUZMOTh2';
  const AUTHORIZE_ID = 'raa-EClh0AuHE8XbHWEO';
}

// Define the classes we will be using.
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

try {
    echo "Attempting to create a test client...\n";

    // Authenticate with the API using the correct method.
    $o_config = MyConfig::create(WlRegionSid::US_EAST_1);
    
    $o_notepad = new NotepadModel($o_config);
    $o_notepad->get();

    $o_enter = new EnterModel($o_config);
    $o_enter->cookieSet($o_notepad->cookieGet());
    $o_enter->s_login = $_ENV['WL_LOGIN'];
    $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']);
    $o_enter->s_notepad = $o_notepad->s_notepad;
    $o_enter->post();

    // Use the EditModel to create the new client profile.
    $o_profile = new EditModel($o_config);
    $a_change = [
        's_first_name' => ['s_value' => 'RailwayFinal'],
        's_last_name'  => ['s_value' => 'Test'],
        's_email'      => ['s_value' => 'railway-final-test-' . time() . '@example.com']
    ];
    
    $o_profile->a_change = $a_change;
    $o_profile->k_business = $_ENV['WL_BUSINESS_ID'];

    // Send the request to create the client.
    $a_result = $o_profile->post();

    // Send a success response to the logs.
    echo "✅ Success! Client created successfully in WellnessLiving.\n";
    echo "New Client UID: " . ($a_result['uid'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    // If anything goes wrong, send a detailed error response to the logs.
    echo "❌ Error: An API error occurred: " . $e->getMessage() . "\n";
}
?>