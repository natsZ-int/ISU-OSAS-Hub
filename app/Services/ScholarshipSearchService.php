<?php

namespace App\Services;

use App\Enums\OverallEligibility;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ScholarshipSearchService
{
    public const ALLOWED_SORTS       = ['deadline', 'name', 'match_score', 'newest'];
    public const ALLOWED_ELIGIBILITY = ['all', 'eligible', 'potentially_eligible', 'not_eligible'];
    public const ALLOWED_DEADLINES   = ['all', 'within_7_days', 'within_30_days'];
    public const MAX_SEARCH_LENGTH   = 200;
    public const DEFAULT_PAGE_SIZE   = 10;
    public const MAX_CANDIDATE_LIMIT = 500;

    public function __construct(
        protected ScholarshipMatchingService $matchingService,
        protected ScholarshipEligibilityService $eligibilityService,
    ) {}

    /**
     * Main search entry point.
     */
    public function search(Request $request, ?StudentProfile $profile = null): LengthAwarePaginator
    {
        $sort = $this->normalizeSort($request->input('sort'));

        // Matching weights are identical for every scholarship in this request:
        // read them once instead of once per evaluated scholarship.
        $weights = ScholarshipMatchingWeight::active();

        // Match-score sort requires in-memory sorting
        if ($sort === 'match_score' && $profile) {
            return $this->searchWithMatchScoreSort($request, $profile, $weights);
        }

        // Eligibility filter requires in-memory evaluation
        $eligibility = $this->normalizeEligibility($request->input('eligibility'));
        if ($eligibility !== 'all' && $profile) {
            return $this->searchWithEligibilityFilter($request, $profile, $eligibility, $sort, $weights);
        }

        // Default: fast database sort
        return $this->searchWithDatabaseSort($request, $sort, $profile, $weights);
    }

    // ── Fast Path: Database Sort ────────────────────

    protected function searchWithDatabaseSort(
        Request $request,
        string $sort,
        ?StudentProfile $profile,
        ?ScholarshipMatchingWeight $weights
    ): LengthAwarePaginator {
        $query = $this->buildBaseQuery($request);
        $this->applyDatabaseSort($query, $sort);

        $paginator = $query->paginate(self::DEFAULT_PAGE_SIZE)->withQueryString();

        // Attach match scores for view display only
        if ($profile) {
            $this->attachMatchScores($paginator->getCollection(), $profile, weights: $weights);
        }

        return $paginator;
    }

    // ── Match-Score Sort Path (PHP) ─────────────────

    protected function searchWithMatchScoreSort(
        Request $request,
        StudentProfile $profile,
        ?ScholarshipMatchingWeight $weights
    ): LengthAwarePaginator {
        $candidates = $this->buildBaseQuery($request)
            ->limit(self::MAX_CANDIDATE_LIMIT)
            ->get();

        $applications = $this->getStudentApplications($profile);

        $scored = $candidates->map(function (Scholarship $s) use ($profile, $applications, $weights) {
            $app = $applications->get($s->id);
            $result = $this->matchingService->evaluateMatch($profile, $s, $app, $weights);
            $s->match_result = $result;
            $s->match_score  = $result->matchScore;
            return $s;
        });

        // Sort: eligible first, then score descending, then tie-breakers
        $sorted = $scored->sort(function (Scholarship $a, Scholarship $b) {
            $aEligible = $a->match_result?->isEligible() ? 1 : 0;
            $bEligible = $b->match_result?->isEligible() ? 1 : 0;

            if ($aEligible !== $bEligible) return $bEligible <=> $aEligible;

            $scoreComp = ($b->match_score ?? 0) <=> ($a->match_score ?? 0);
            if ($scoreComp !== 0) return $scoreComp;

            $deadlineComp = $a->application_deadline <=> $b->application_deadline;
            if ($deadlineComp !== 0) return $deadlineComp;

            $titleComp = strcmp($a->title, $b->title);
            if ($titleComp !== 0) return $titleComp;

            return $a->id <=> $b->id;
        })->values();

        return $this->paginateCollection($sorted, $request);
    }

    // ── Eligibility Filter Path (PHP) ───────────────

    protected function searchWithEligibilityFilter(
        Request $request,
        StudentProfile $profile,
        string $eligibility,
        string $sort,
        ?ScholarshipMatchingWeight $weights
    ): LengthAwarePaginator {
        $candidates = $this->buildBaseQuery($request)
            ->limit(self::MAX_CANDIDATE_LIMIT)
            ->get();

        $applications = $this->getStudentApplications($profile);

        $filtered = $candidates->filter(function (Scholarship $s) use ($profile, $eligibility, $applications) {
            $app = $applications->get($s->id);
            $result = $this->eligibilityService->evaluate($profile, $s, $app);
            $s->eligibility_result = $result;
            $isEligible = $result->overall === OverallEligibility::ELIGIBLE;

            return match ($eligibility) {
                'eligible'             => $isEligible,
                'potentially_eligible' => ! $isEligible && $this->isPotentiallyEligible($profile, $s),
                'not_eligible'         => ! $isEligible && ! $this->isPotentiallyEligible($profile, $s),
                default                => true,
            };
        });

        // Sort the filtered list
        $sorted = match ($sort) {
            'name'   => $filtered->sortBy('title')->values(),
            'newest' => $filtered->sortByDesc('created_at')->values(),
            default  => $filtered->sortBy('application_deadline')->values(),
        };

        $this->attachMatchScores($sorted, $profile, $applications, $weights);

        return $this->paginateCollection($sorted, $request);
    }

    // ── Query Building ──────────────────────────────

    protected function buildBaseQuery(Request $request): Builder
    {
        $query = Scholarship::query()
            ->availableForStudents()
            ->with('requirements');

        $this->applyKeywordSearch($query, $request->input('search'));
        $this->applyProgramFilter($query, $request->input('program'));
        $this->applyYearLevelFilter($query, $request->input('year_level'));
        $this->applyTypeFilter($query, $request->input('type'));
        $this->applyDeadlineFilter($query, $request->input('deadline'));

        return $query;
    }

    // ── Keyword Search ──────────────────────────────

    protected function applyKeywordSearch(Builder $query, ?string $rawSearch): void
    {
        $normalized = $this->normalizeSearchInput($rawSearch);
        if ($normalized === null) return;

        $tokens = explode(' ', $normalized);

        $query->where(function (Builder $outer) use ($tokens) {
            foreach ($tokens as $token) {
                $escaped = addcslashes($token, '%_\\');
                $like = '%' . $escaped . '%';

                $outer->where(function (Builder $inner) use ($like) {
                    $inner->where('title', 'like', $like)
                          ->orWhere('provider', 'like', $like)
                          ->orWhere('scholarship_type', 'like', $like)
                          ->orWhere('description', 'like', $like)
                          ->orWhere('eligible_programs', 'like', $like);
                });
            }
        });
    }

    public function normalizeSearchInput(?string $input): ?string
    {
        if ($input === null) return null;
        $trimmed = trim($input);
        if ($trimmed === '') return null;
        $collapsed = preg_replace('/\s+/', ' ', $trimmed);
        return Str::limit($collapsed, self::MAX_SEARCH_LENGTH, '');
    }

    // ── Program Filter (Database Agnostic) ──────────

    // Using Laravel's database-agnostic JSON features so tests pass on SQLite
    protected function applyProgramFilter(Builder $query, ?string $program): void
    {
        if (blank($program)) return;
        $safeProgram = trim($program);
        if (strlen($safeProgram) > 100) return;

        $query->where(function (Builder $q) use ($safeProgram) {
            $q->whereNull('eligible_programs')
              ->orWhere('eligible_programs', '[]')
              ->orWhereJsonContains('eligible_programs', $safeProgram);
        });
    }

    // ── Year Level Filter (Database Agnostic) ───────

    // Using Laravel's database-agnostic JSON features so tests pass on SQLite
    protected function applyYearLevelFilter(Builder $query, ?string $yearLevel): void
    {
        if (blank($yearLevel)) return;
        $yl = (int) $yearLevel;
        if ($yl < 1 || $yl > 6) return;

        $query->where(function (Builder $q) use ($yl) {
            $q->whereNull('eligible_year_levels')
              ->orWhere('eligible_year_levels', '[]')
              ->orWhereJsonContains('eligible_year_levels', $yl);
        });
    }

    // ── Type Filter ─────────────────────────────────

    protected function applyTypeFilter(Builder $query, ?string $type): void
    {
        if (blank($type)) return;
        $safeType = trim($type);
        if (strlen($safeType) > 100) return;

        $query->where('scholarship_type', $safeType);
    }

    // ── Deadline Filter ─────────────────────────────

    protected function applyDeadlineFilter(Builder $query, ?string $deadline): void
    {
        if (blank($deadline) || ! in_array($deadline, self::ALLOWED_DEADLINES, true)) return;

        $today = now()->toDateString();

        match ($deadline) {
            'within_7_days'  => $query->whereBetween('application_deadline', [$today, now()->addDays(7)->toDateString()]),
            'within_30_days' => $query->whereBetween('application_deadline', [$today, now()->addDays(30)->toDateString()]),
            default          => null,
        };
    }

    // ── Sorting ─────────────────────────────────────

    public function normalizeSort(?string $sort): string
    {
        if (in_array($sort, self::ALLOWED_SORTS, true)) return $sort;
        return 'deadline';
    }

    public function normalizeEligibility(?string $eligibility): string
    {
        if (in_array($eligibility, self::ALLOWED_ELIGIBILITY, true)) return $eligibility;
        return 'all';
    }

    protected function applyDatabaseSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name'   => $query->orderBy('title', 'asc')
                              ->orderBy('application_deadline', 'asc')
                              ->orderBy('id', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc')
                              ->orderBy('title', 'asc')
                              ->orderBy('id', 'asc'),
            default  => $query->orderBy('application_deadline', 'asc')
                              ->orderBy('title', 'asc')
                              ->orderBy('id', 'asc'),
        };
    }

    // ── Score Attachment ────────────────────────────

    protected function attachMatchScores(
        Collection $scholarships,
        StudentProfile $profile,
        ?Collection $applications = null,
        ?ScholarshipMatchingWeight $weights = null
    ): void {
        $applications ??= $this->getStudentApplications($profile);

        foreach ($scholarships as $scholarship) {
            $app = $applications->get($scholarship->id);
            $result = $this->matchingService->evaluateMatch($profile, $scholarship, $app, $weights);
            $scholarship->match_result = $result;
            $scholarship->match_score  = $result->matchScore;
        }
    }

    // ── Potentially Eligible Heuristic ──────────────

    protected function isPotentiallyEligible(StudentProfile $profile, Scholarship $scholarship): bool
    {
        if ($scholarship->minimum_gwa !== null && $profile->gwa !== null) {
            if ((float) $profile->gwa > (float) $scholarship->minimum_gwa) {
                return false;
            }
        }

        if ($scholarship->maximum_family_income !== null && $profile->family_income !== null) {
            if ((float) $profile->family_income > (float) $scholarship->maximum_family_income) {
                return false;
            }
        }

        if (! empty($scholarship->eligible_programs) && ! blank($profile->program)) {
            $normalized = array_map(fn ($p) => strtoupper(trim($p)), $scholarship->eligible_programs);
            if (! in_array(strtoupper(trim($profile->program)), $normalized, true)) {
                return false;
            }
        }

        if (! empty($scholarship->eligible_year_levels) && $profile->year_level !== null) {
            $normalized = array_map('intval', $scholarship->eligible_year_levels);
            if (! in_array((int) $profile->year_level, $normalized, true)) {
                return false;
            }
        }

        return true;
    }

    // ── Helpers ─────────────────────────────────────

    protected function getStudentApplications(StudentProfile $profile): Collection
    {
        // Relationships read during match/eligibility scoring are eager loaded
        // so evaluation never issues per-application queries inside the loop.
        return ScholarshipApplication::where('student_profile_id', $profile->id)
            ->with(['documents', 'scholarship.requirements'])
            ->get()
            ->keyBy('scholarship_id');
    }

    protected function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = self::DEFAULT_PAGE_SIZE;
        $slice   = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new Paginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path'     => $request->url(),
                'pageName' => 'page',
            ]
        );

        return $paginator->withQueryString();
    }

    public function availableFilterOptions(): array
    {
        return [
            'types'    => Scholarship::availableForStudents()->distinct()->pluck('scholarship_type')->filter()->values(),
            'programs' => $this->extractAllPrograms(),
        ];
    }

    protected function extractAllPrograms(): Collection
    {
        $programs = collect();
        Scholarship::availableForStudents()
            ->whereNotNull('eligible_programs')
            ->pluck('eligible_programs')
            ->each(function ($list) use ($programs) {
                if (is_array($list)) {
                    foreach ($list as $p) $programs->push($p);
                }
            });
        return $programs->unique()->sort()->values();
    }
}