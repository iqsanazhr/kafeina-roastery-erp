<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$entries = DB::table('migrations')->get();
echo 'Migrations table entries count: '.$entries->count()."\n";
foreach ($entries as $e) {
    echo "ID: {$e->id} | Migration: {$e->migration} | Batch: {$e->batch}\n";
}
