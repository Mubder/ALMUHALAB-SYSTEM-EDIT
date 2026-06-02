<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\Role::where('name', 'Admin')->first();
$count = $admin ? $admin->permissions()->count() : 0;
echo "perm_count:$count\n";
