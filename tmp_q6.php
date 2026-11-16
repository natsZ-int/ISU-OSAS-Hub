<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Storage;
echo "application_documents:\n";
foreach (DB::table('application_documents')->get() as $d) {
    echo $d->id.' path='.$d->file_path.' exists='.var_export(Storage::disk('local')->exists($d->file_path), true).PHP_EOL;
}
echo "guidance/appointments rows: ".DB::table('guidance_appointments')->count().PHP_EOL;
