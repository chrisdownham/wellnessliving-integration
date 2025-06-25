<?php
echo "--- Starting Autoloader Debug ---\n\n";

$loader_file = __DIR__.'/vendor/autoload.php';

if (!file_exists($loader_file)) {
    echo "FATAL: vendor/autoload.php was not found. 'composer install' may have failed.\n";
    exit(1);
}

// Include the autoloader
$loader = require $loader_file;

echo "SUCCESS: vendor/autoload.php was included.\n\n";

// Get the map of known class prefixes
$prefixes = $loader->getPrefixesPsr4();

echo "--- Registered PSR-4 Prefixes ---\n";
print_r($prefixes);
echo "\n---------------------------------\n\n";


echo "--- Checking for WellnessLiving\Config\WlConfigDeveloper ---\n";
if (class_exists('WellnessLiving\Config\WlConfigDeveloper')) {
    echo "✅ SUCCESS: The class 'WellnessLiving\Config\WlConfigDeveloper' was found by the autoloader.\n";
} else {
    echo "❌ FAILURE: The class 'WellnessLiving\Config\WlConfigDeveloper' was NOT found by the autoloader.\n";
}

echo "\n--- Debug Script Finished ---\n";
?>