<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo 'Default DB: '.config('database.default')."\n";
echo 'MySQL DB: '.config('database.connections.mysql.database')."\n";
echo 'Has migrations table: '.(Schema::hasTable('migrations') ? 'YES' : 'NO')."\n";

$tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'kafeina_erp'");
echo 'Tables in kafeina_erp: '.count($tables)."\n";
foreach ($tables as $t) {
    echo ' - '.$t->TABLE_NAME."\n";
}

$migCount = DB::table('migrations')->count();
echo 'Migration entries: '.$migCount."\n";
