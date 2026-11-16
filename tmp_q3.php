<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try { foreach (DB::select('SHOW BINARY LOGS') as $l) { echo json_encode((array)$l).PHP_EOL; } } catch (Exception $e) { echo "binlog err: ".$e->getMessage().PHP_EOL; }
try { foreach (DB::select('SHOW VARIABLES LIKE "%binlog%"') as $v) { echo $v->Variable_name.'='.$v->Value.PHP_EOL; } } catch (Exception $e) {}
echo "service types:\n";
foreach (DB::table('service_types')->get() as $s) { echo $s->id.' '.$s->name.PHP_EOL; }
echo "profiles:\n";
foreach (DB::table('student_profiles')->get() as $p) { echo $p->id.' user='.$p->user_id.' '.($p->first_name??'').' '.($p->last_name??'').PHP_EOL; }
echo "requirements:\n";
foreach (DB::table('service_type_requirements')->get() as $r) { echo $r->id.' st='.$r->service_type_id.' '.$r->name.' required='.var_export($r->is_required,true).PHP_EOL; }
