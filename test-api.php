<?php

// This line includes the SDK's autoloader, which makes all the SDK classes available.
require_once 'WellnessLiving/wl-autoloader.php';

/**
 * This is our custom configuration class that extends the SDK's blueprint.
 * This is the correct way to provide your permanent Application Credentials.
 */
class MyConfig extends \WellnessLiving\Config\WlConfigDeveloper
{
  /**
   * Your permanent WellnessLiving Authorization Code.
   */
  const AUTHORIZE_CODE = 'eBYCKvZ90FdqbLoRTb44tWOARpuZPLBaFphaSUZMOTh2';

  /**
   * Your permanent WellnessLiving Application ID.
   */
  const AUTHORIZE_ID = 'raa-EClh0AuHE8XbHWEO';
}

// --- The main part of our script starts here ---

echo "Attempting to connect to WellnessLiving API...\n\n";

try {
    // Step 1: Define your temporary user credentials (username and password).
    $a_credential = [
        's_login' => 'ctdownham@googlemail.com',
        's_password' => '168421ctd'
    ];

    // Step 2: Create a configuration object from our new custom class.
    // We pass the user credentials to it.
    $o_config = new MyConfig($a_credential);

    // Step 3: Prepare and make the API call, just like before.
    $k_business = '48278';
    $o_business = new \WellnessLiving\Wl\Business\BusinessModel($o_config);
    $o_business->k_business = $k_business;

    $o_business->get();

    // Step 4: Show the successful result!
    echo "✅ API Call Successful!\n\n";
    echo "Business Data Received:\n";
    print_r($o_business->dataGet());

} catch (Exception $e) {
    echo "❌ Error: ".$e->getMessage();
}

echo "\n\nScript finished.\n";

?>