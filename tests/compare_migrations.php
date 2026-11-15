<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$files = scandir(database_path('migrations'));
$dbMigrations = DB::table('migrations')->pluck('migration')->toArray();

foreach ($files as $file) {
    if (str_ends_with($file, '.php')) {
        $name = str_replace('.php', '', $file);
        $inDb = in_array($name, $dbMigrations);
        echo ($inDb ? '[IN DB]  ' : '[NOT IN] ').$name."\n";
    }
}
