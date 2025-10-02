<?php
/**
 * Debug configuration
 * Run this from your Magento root: php debug_config.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

echo "=== Tagging GTM Configuration Debug ===\n";

// Test the config
$config = $objectManager->get(\Tagging\GTM\Config\Config::class);

echo "Module Enabled: " . ($config->isEnabled() ? 'YES' : 'NO') . "\n";
echo "Placed by Plugin: " . ($config->isPlacedByPlugin() ? 'YES' : 'NO') . "\n";
echo "GTM URL: '" . $config->getGoogleTagmanagerUrl() . "'\n";
echo "GTM URL Length: " . strlen($config->getGoogleTagmanagerUrl()) . "\n";

// Test the condition from iframe.phtml
$condition = $config->isEnabled() && $config->getGoogleTagmanagerUrl() && strlen($config->getGoogleTagmanagerUrl()) > 0 && $config->isPlacedByPlugin();
echo "iframe.phtml condition result: " . ($condition ? 'TRUE' : 'FALSE') . "\n";

if ($condition) {
    echo "Script would be loaded from: https://" . $config->getGoogleTagmanagerUrl() . "/user-data-minified.es.js\n";
} else {
    echo "Script would NOT be loaded\n";
}

echo "\n=== Check logs ===\n";
echo "Run: tail -f var/log/system.log | grep 'Tagging GTM'\n";
