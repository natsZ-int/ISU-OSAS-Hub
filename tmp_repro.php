<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$cases = [
    // [user id, request id, doc id]
    [3, 1, 1],
    [3, 1, 2],
    [4, 2, 3],
];

foreach ($cases as [$uid, $rid, $did]) {
    Auth::login(App\Models\User::find($uid));
    $uri = "/student/service-requests/{$rid}/documents/{$did}/download";
    $res = $kernel->handle(Request::create($uri, 'GET'));
    $disp = $res->headers->get('Content-Disposition') ?? 'none';
    echo "student u{$uid} doc{$did}: {$res->getStatusCode()} {$disp}\n";
    Auth::logout();
}

// Admin side (the URL the user clicked at 15:15)
Auth::login(App\Models\User::find(2));
$uri = '/admin/student-service-requests/2/documents/3/download';
$res = $kernel->handle(Request::create($uri, 'GET'));
echo 'admin doc3: '.$res->getStatusCode().' '.($res->headers->get('Content-Disposition') ?? 'none')."\n";

echo 'exists checks: '.Storage::disk('local')->exists('private/student-service-requests/1/jdSa3U2Zw17G97lqjxiXTn9ajfMgp6lkyR8pgcHd.jpg').' '.Storage::disk('local')->exists('private/student-service-requests/1/uwNxoD7lwQKhMxJxI0LLvTVE93GSqU3QZJiy5yuK.jpg').' '.Storage::disk('local')->exists('private/student-service-requests/2/UjxNb80tPvPDHQ5MiiTxNsDJDhjKKHGMBhJwIg5T.jpg')."\n";
