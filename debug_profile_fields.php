<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

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

// Fetch “create client” profile fields
\$edit = new EditModel(\$cfg);
\$edit->cookieSet(\$notepad->cookieGet());
\$edit->k_business = \$_ENV['WL_BUSINESS_ID'];
\$edit->get();

// Dump as JSON
header('Content-Type: application/json', true, 200);
echo json_encode(\$edit->a_field_list);
