pbpaste > example-sdk.php <<'EOL'
<?php

// Use Composer's autoloader for all classes.
require_once 'vendor/autoload.php';
require_once 'example-config.php';

// This securely loads the variables from your .env file.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define the classes we will be using.
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Report\DataModel;
use WellnessLiving\Wl\Report\WlReportGroupSid;
use WellnessLiving\Wl\Report\WlReportSid;
use WellnessLiving\WlRegionSid;
use WellnessLiving\Wl\WlAssertException;
use WellnessLiving\Wl\WlUserException;

try
{
  // 1. SIGNING IN A USER
  $o_config = ExampleConfig::create(WlRegionSid::US_EAST_1);

  $o_notepad = new NotepadModel($o_config);
  $o_notepad->get();

  // Sign in the user using credentials from the .env file.
  $o_enter = new EnterModel($o_config);
  $o_enter->cookieSet($o_notepad->cookieGet());
  $o_enter->s_login = $_ENV['WL_LOGIN']; // Using secure variable
  $o_enter->s_notepad = $o_notepad->s_notepad;
  $o_enter->s_password = $o_notepad->hash($_ENV['WL_PASSWORD']); // Using secure variable
  $o_enter->post();

  // 2. EXECUTING THE REQUEST
  $o_report = new DataModel($o_config);
  $o_report->cookieSet($o_notepad->cookieGet());
  $o_report->id_report_group = WlReportGroupSid::DAY;
  $o_report->id_report = WlReportSid::PURCHASE_ITEM_ACCRUAL_CASH;
  $o_report->k_business = $_ENV['WL_BUSINESS_ID']; // Using secure variable
  $o_report->filterSet([
    'dt_date' => date('Y-m-d')
  ]);
  $o_report->get();

  echo "✅ API Call Successful! Report contains 0 rows.";
}
catch(Exception $e)
{
  echo '❌ Error: '.$e->getMessage();
}

?>
EOL