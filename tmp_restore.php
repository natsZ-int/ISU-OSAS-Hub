<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Row images recovered from MySQL binlog (MSI-bin.000189, deletes at 2026-11-11 15:13:34).
$dt = fn ($epoch) => date('Y-m-d H:i:s', $epoch);

DB::table('student_service_requests')->insert([
    'id' => 2,
    'request_number' => 'SR-2026-000002',
    'student_profile_id' => 2,
    'service_type_id' => 3,
    'description' => 'lung cancer',
    'status' => 'completed',
    'submitted_at' => $dt(1792397050),
    'completed_at' => $dt(1792397112),
    'cancelled_at' => null,
    'cancelled_by' => null,
    'remarks' => 'cdadsfadsfdsfa',
    'internal_remarks' => 'fasdgfuas',
    'handled_by' => null,
    'created_at' => $dt(1792397050),
    'updated_at' => $dt(1792397112),
]);

DB::table('service_request_documents')->insert([
    'id' => 3,
    'student_service_request_id' => 2,
    'service_type_requirement_id' => 4,
    'file_path' => 'private/student-service-requests/2/UjxNb80tPvPDHQ5MiiTxNsDJDhjKKHGMBhJwIg5T.jpg',
    'original_filename' => '2x2.jpg',
    'mime_type' => 'image/jpeg',
    'file_size' => 40495,
    'uploaded_by' => 4,
    'created_at' => $dt(1792397051),
    'updated_at' => $dt(1792397051),
]);

DB::table('student_service_request_histories')->insert([
    [
        'id' => 4,
        'student_service_request_id' => 2,
        'previous_status' => null,
        'new_status' => 'submitted',
        'changed_by' => 4,
        'remarks' => 'Service request submitted by student.',
        'created_at' => $dt(1792397050),
    ],
    [
        'id' => 5,
        'student_service_request_id' => 2,
        'previous_status' => 'submitted',
        'new_status' => 'processing',
        'changed_by' => 2,
        'remarks' => 'aduyasgfa',
        'created_at' => $dt(1792397101),
    ],
    [
        'id' => 6,
        'student_service_request_id' => 2,
        'previous_status' => 'processing',
        'new_status' => 'completed',
        'changed_by' => 2,
        'remarks' => 'cdadsfadsfdsfa',
        'created_at' => $dt(1792397112),
    ],
]);

echo "restored\n";
foreach (DB::table('student_service_requests')->orderBy('id')->get() as $r) {
    echo "REQ {$r->id} {$r->request_number} {$r->status} desc={$r->description} submitted={$r->submitted_at} completed={$r->completed_at} remarks={$r->remarks} internal={$r->internal_remarks}\n";
}
foreach (DB::table('service_request_documents')->orderBy('id')->get() as $d) {
    echo "DOC {$d->id} req={$d->student_service_request_id} reqmt={$d->service_type_requirement_id} {$d->original_filename} {$d->file_size}B by={$d->uploaded_by} at={$d->created_at}\n";
}
foreach (DB::table('student_service_request_histories')->orderBy('id')->get() as $h) {
    echo "HIS {$h->id} req={$h->student_service_request_id} {$h->previous_status}->{$h->new_status} by={$h->changed_by} at={$h->created_at} :: {$h->remarks}\n";
}
