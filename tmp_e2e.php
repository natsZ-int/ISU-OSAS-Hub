<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Let the scripted request skip CSRF verification (framework skips it while env=testing).
$app['env'] = 'testing';

$user = App\Models\User::find(4);
Auth::login($user);

$serviceType = App\Models\ServiceType::where('is_active', 1)->with('requirements')->first();
echo "service type: {$serviceType->id} {$serviceType->name}\n";

$before = [
    'requests' => DB::table('student_service_requests')->max('id'),
    'docs' => DB::table('service_request_documents')->max('id'),
];

$params = [
    'service_type_id' => $serviceType->id,
    'description' => 'Temporary end-to-end download verification request.',
];

$files = [];
foreach ($serviceType->requirements as $req) {
    if ($req->is_required) {
        $tmp = tempnam(sys_get_temp_dir(), 'upl').'.jpg';
        file_put_contents($tmp, base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q=='));
        $files["requirement_{$req->id}"] = new UploadedFile($tmp, 'verify-upload.jpg', 'image/jpeg', null, true);
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::create('/student/service-requests', 'POST', $params, [], $files);
$response = $kernel->handle($request);

echo 'store status: '.$response->getStatusCode()."\n";
echo 'location: '.($response->headers->get('Location') ?? 'none')."\n";

$newRequest = App\Models\StudentServiceRequest::where('id', '>', $before['requests'])->first();
$doc = App\Models\ServiceRequestDocument::where('id', '>', $before['docs'])->first();

if (! $newRequest || ! $doc) {
    echo "NO ROWS CREATED (store did not succeed)\n";
    exit(1);
}

echo "new request id: {$newRequest->id} {$newRequest->request_number}\n";
echo "doc id: {$doc->id} path: {$doc->file_path}\n";
echo 'storage exists: '.var_export(Storage::disk('local')->exists($doc->file_path), true)."\n";
echo 'physical: '.storage_path('app/private/'.$doc->file_path).' exists='.var_export(file_exists(storage_path('app/private/'.$doc->file_path)), true)."\n";

$uri = "/student/service-requests/{$newRequest->id}/documents/{$doc->id}/download";
$dl = $kernel->handle(Request::create($uri, 'GET'));
echo 'download status: '.$dl->getStatusCode()."\n";
echo 'content-disposition: '.($dl->headers->get('Content-Disposition') ?? 'none')."\n";

// ---- cleanup (only rows created by this script) ----
Storage::disk('local')->delete($doc->file_path);
$doc->delete();
App\Models\StudentServiceRequestHistory::where('student_service_request_id', $newRequest->id)->delete();
$newRequest->delete();
echo "cleanup done\n";
