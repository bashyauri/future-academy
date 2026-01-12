<?php
// optimize.php — Run once, then delete!
require __DIR__.'/../laravel_staging/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel_staging/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('optimize:clear');  // Clears everything
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');

echo "Caches cleared and rebuilt! Check /login now.";
?>
