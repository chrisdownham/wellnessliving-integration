<?php
require_once 'vendor/autoload.php';
require_once 'example-config.php';

use WellnessLiving\Core\Passport\Login\Enter\NotepadModel;
use WellnessLiving\Core\Passport\Login\Enter\EnterModel;
use WellnessLiving\Wl\Profile\Edit\EditModel;
use WellnessLiving\WlRegionSid;

// Authenticate
\$config  = ExampleConfig::create(WlRegionSid::US_EAST_1);
\$notepad = new NotepadModel(\$config);
\$notepad->get();

\$enter   = new EnterModel(\$config);
\$enter->cookieSet(\$notepad->cookieGet());
\$enter->s_login    = \$_ENV['WL_LOGIN'];
\$enter->s_notepad  = \$notepad->s_notepad;
\$enter->s_password = \$notepad->hash(\$_ENV['WL_PASSWORD']);
\$enter->post();

// Fetch the profile “create client” fields
\$edit = new EditModel(\$config);
\$edit->cookieSet(\$notepad->cookieGet());
\$edit->k_business = \$_ENV['WL_BUSINESS_ID'];
\$edit->get();

// Output pure JSON
header('Content-Type: application/json', true, 200);
echo json_encode(\$edit->a_field_list);
