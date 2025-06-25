<?php

require_once 'WellnessLiving/wl-autoloader.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Wl\Report\DataModel;
use WellnessLiving\Wl\Report\WlReportGroupSid; // This path is corrected.
use WellnessLiving\Wl\Report\WlReportSid;      // This path is corrected.
use WellnessLiving\WlRegionSid;
use WellnessLiving\Wl\WlAssertException;
use WellnessLiving\Wl\WlUserException;

try
{
  // 1. SIGNING IN A USER
  $o_config = ExampleConfig::create(WlRegionSid::US_EAST_1);

  $o_notepad = new NotepadModel($o_config);
  $o_notepad->get();

  // Sign in the user with the new, hashed password.
  $o_enter = new EnterModel($o_config);
  $o_enter->cookieSet($o_notepad->cookieGet());
  $o_enter->s_login = 'ctdownham@googlemail.com';
  $o_enter->s_notepad = $o_notepad->s_notepad;
  $o_enter->s_password = $o_notepad->hash('Rise123@'); // Your new password
  $o_enter->post();

  // 2. EXECUTING THE REQUEST
  $o_report = new DataModel($o_config);
  $o_report->cookieSet($o_notepad->cookieGet());
  $o_report->id_report_group = WlReportGroupSid::DAY;
  $o_report->id_report = WlReportSid::PURCHASE_ITEM_ACCRUAL_CASH;
  $o_report->k_business = '48278';
  $o_report->filterSet([
    'dt_date' => date('Y-m-d')
  ]);
  $o_report->get();

  // 3. USING THE RESULT
  $i = 0;
  if(isset($o_report->a_data['a_row']) && count($o_report->a_data['a_row']))
  {
    foreach($o_report->a_data['a_row'] as $a_row)
    {
      $i++;
      echo $i.'. '.$a_row['dt_date'].' '.$a_row['f_total']['m_amount'].' '.$a_row['o_user']['text_name'].' '.$a_row['s_item']."\r\n";
    }
  }
  else
  {
    echo "✅ API Call Successful! The All Sales Report for today contains 0 rows.";
  }
}
catch(WlAssertException $e)
{
  echo 'Assert Exception: '.$e->getMessage();
  return;
}
catch(WlUserException $e)
{
  echo 'User Exception: '.$e->getMessage()."\n";
  return;
}
?>
