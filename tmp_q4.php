<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (DB::select('SHOW VARIABLES LIKE "%datadir%"') as $v) echo $v->Value.PHP_EOL;
foreach (DB::select('SHOW VARIABLES LIKE "log_bin%%"') as $v) echo $v->Variable_name.'='.$v->Value.PHP_EOL;
