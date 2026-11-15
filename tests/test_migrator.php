<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$migrator = $app->make('migrator');
$repository = $migrator->getRepository();

echo 'Repository exists: '.($repository->repositoryExists() ? 'YES' : 'NO')."\n";
$ran = $repository->getRan();
echo 'Ran count: '.count($ran)."\n";
print_r($ran);

$batches = $repository->getMigrationBatches();
echo 'Batches count: '.count($batches)."\n";
print_r($batches);
