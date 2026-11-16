<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (DB::table('service_type_requirements')->get() as $r) { echo $r->id.' st='.$r->service_type_id.' '.$r->name.' required='.var_export((bool)$r->is_required,true).PHP_EOL; }
echo 'session driver='.config('session.driver').' env binding='; try { echo var_export($app['env'], true); } catch (Exception $e) { echo 'n/a'; } echo PHP_EOL;
