<?php
define('LARAVEL_START', microtime(true));

// Load Laravel autoloader and app bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Instantiate kernel to execute Artisan commands programmatically
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<h2>🧼 Clearing Laravel Production Caches...</h2>";

$status = $kernel->call('route:clear');
echo "✔️ Route Clear: " . ($status === 0 ? "Success" : "Failed") . "<br>";

$status2 = $kernel->call('config:clear');
echo "✔️ Config Clear: " . ($status2 === 0 ? "Success" : "Failed") . "<br>";

$status3 = $kernel->call('cache:clear');
echo "✔️ Application Cache Clear: " . ($status3 === 0 ? "Success" : "Failed") . "<br>";

echo "<p>✨ All caches cleared! Please try reloading your integration endpoint.</p>";
