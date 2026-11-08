<?php

namespace App\Http\Controllers;

use App\Services\LiveSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuickSearchController extends Controller
{
    public function __construct(
        protected LiveSearchService $search,
    ) {}

    /**
     * Suggestions for administrator-facing pages.
     */
    public function admin(Request $request, string $module): JsonResponse
    {
        Gate::authorize('manage-users');

        return $this->respond($request, function (string $term, int $limit) use ($module) {
            return $this->search->adminSearch($module, $term, $limit);
        });
    }

    /**
     * Suggestions for student-facing pages, scoped to the signed-in student.
     */
    public function student(Request $request, string $module): JsonResponse
    {
        $user = $request->user();

        return $this->respond($request, function (string $term, int $limit) use ($module, $user) {
            return $this->search->studentSearch($module, $term, $user, $limit);
        });
    }

    /**
     * Suggestions for the counselor's own appointments.
     */
    public function counselor(Request $request, string $module): JsonResponse
    {
        $user = $request->user();

        return $this->respond($request, function (string $term, int $limit) use ($module, $user) {
            return $this->search->counselorSearch($module, $term, $user, $limit);
        });
    }

    /**
     * Suggestions for the OSAS enrollment-verification queue.
     */
    public function enrollment(Request $request): JsonResponse
    {
        Gate::authorize('manage-enrollment-verifications');

        return $this->respond($request, function (string $term, int $limit) {
            return $this->search->enrollmentSearch($term, $limit);
        });
    }

    /**
     * Suggestions for the signed-in user's own notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->respond($request, function (string $term, int $limit) use ($user) {
            return $this->search->notificationSearch($term, $user, $limit);
        });
    }

    /**
     * Validate the request and build the shared JSON envelope.
     */
    protected function respond(Request $request, callable $resolver): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:'.LiveSearchService::MAX_TERM_LENGTH],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.LiveSearchService::MAX_LIMIT],
        ]);

        $term = $this->search->normalizeTerm($validated['q'] ?? null);
        $limit = (int) ($validated['limit'] ?? LiveSearchService::DEFAULT_LIMIT);

        $results = $term === null ? [] : $resolver($term, $limit);

        return response()->json([
            'query' => $term ?? '',
            'count' => count($results),
            'results' => $results,
        ]);
    }
}
