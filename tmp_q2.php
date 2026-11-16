<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "audit (service):\n";
foreach (DB::table('audit_logs')->where('record_type','StudentServiceRequest')->orWhere('description','like','%SR-2026-000002%')->orWhere('description','like','%000002%')->get() as $a) { echo json_encode((array)$a).PHP_EOL; }
echo "notifications:\n";
foreach (DB::table('notifications')->where('data','like','%000002%')->get() as $n) { echo json_encode((array)$n).PHP_EOL; }
echo "tables:\n";
print_r(DB::select('show tables'));
