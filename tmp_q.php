<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "requests:\n";
foreach (DB::table('student_service_requests')->get() as $r) { echo json_encode((array) $r).PHP_EOL; }
echo "documents:\n";
foreach (DB::table('service_request_documents')->get() as $d) { echo json_encode((array) $d).PHP_EOL; }
echo "histories:\n";
foreach (DB::table('student_service_request_histories')->get() as $h) { echo json_encode((array) $h).PHP_EOL; }
echo "softdeletes on model: ";
$m = new App\Models\StudentServiceRequest();
echo (in_array(Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($m)) ? 'yes' : 'no').PHP_EOL;
