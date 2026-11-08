<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ScholarshipAnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private ScholarshipAnnouncementService $announcements,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('manage-users');

        $query = User::with(['role', 'studentProfile']);

        // Search
        if ($search = $request->filled('search') ? $request->input('search') : null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', function ($sq) use ($search) {
                        $sq->where('student_number', 'like', "%{$search}%");
                    });
            });
        }

        // Role filter
        if ($roleName = $request->filled('role') ? $request->input('role') : null) {
            if (in_array($roleName, [Role::ADMIN, Role::COUNSELOR, Role::STUDENT])) {
                $query->whereHas('role', fn ($q) => $q->where('name', $roleName));
            }
        }

        // Status filter
        if ($status = $request->filled('status') ? $request->input('status') : null) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        // Live search: the table refreshes in place without a full page load.
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('admin.users._results', ['users' => $users])->render(),
                'total' => $users->total(),
                'first' => $users->firstItem(),
                'last' => $users->lastItem(),
            ]);
        }

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Typeahead suggestions for the user search box.
     *
     * Returns a small, relevance-ordered set of matches so the search field
     * can predict names, e-mails, and student numbers as the admin types.
     */
    public function suggest(Request $request): JsonResponse
    {
        Gate::authorize('manage-users');

        $term = trim(preg_replace('/\s+/', ' ', (string) $request->input('q', '')));
        $term = Str::limit($term, 100, '');

        if ($term === '') {
            return response()->json(['query' => '', 'results' => []]);
        }

        // Escape LIKE wildcards so "%" and "_" are searched literally.
        $escaped = addcslashes($term, '%_\\');
        $contains = "%{$escaped}%";
        $prefix = "{$escaped}%";
        $needle = Str::lower($term);

        $users = User::query()
            ->with(['role', 'studentProfile'])
            ->where(function ($query) use ($contains) {
                $query->where('name', 'like', $contains)
                    ->orWhere('email', 'like', $contains)
                    ->orWhereHas('studentProfile', fn ($sq) => $sq->where('student_number', 'like', $contains));
            })
            ->orderByRaw(
                'CASE
                    WHEN name LIKE ? THEN 0
                    WHEN email LIKE ? THEN 1
                    ELSE 2
                END',
                [$prefix, $prefix]
            )
            ->orderBy('name')
            ->limit(8)
            ->get();

        $results = $users->map(function (User $user) use ($needle) {
            $studentNumber = $user->studentProfile?->student_number;

            $match = match (true) {
                Str::contains(Str::lower($user->name), $needle) => 'name',
                Str::contains(Str::lower($user->email), $needle) => 'email',
                $studentNumber !== null && Str::contains(Str::lower($studentNumber), $needle) => 'student number',
                default => 'name',
            };

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'student_number' => $studentNumber,
                'role' => $user->role?->label ?? ucfirst($user->role?->name ?? 'N/A'),
                'active' => (bool) $user->is_active,
                'match' => $match,
                'search' => match ($match) {
                    'email' => $user->email,
                    'student number' => $studentNumber ?? $user->name,
                    default => $user->name,
                },
                'url' => route('admin.users.edit', $user),
            ];
        })->values();

        return response()->json([
            'query' => $term,
            'results' => $results,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $request->role_id,
                'password' => Hash::make($request->password),
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Admin-created accounts are treated as email-verified; only
            // self-registered accounts go through the OTP verification flow.
            $user->forceFill(['email_verified_at' => now()])->save();

            // Create student profile if role is student
            if ($user->isStudent()) {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'student_number' => $request->student_number,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'middle_name' => $request->middle_name,
                    'contact_number' => $request->contact_number,
                    'college' => $request->college,
                    'program' => $request->program,
                    'year_level' => $request->year_level ?? 1,
                    'gwa' => $request->gwa,
                    'family_income' => $request->family_income,
                    'address' => $request->address,
                ]);
            }

            DB::commit();

            AuditLogService::log(
                action: 'User Created',
                module: 'User Management',
                recordType: 'User',
                recordId: $user->id,
                description: "Created user '{$user->name}' ({$user->email}) with role '{$user->role->name}'.",
                request: $request
            );

            return redirect()->route('admin.users.index')
                ->with('status', "User '{$user->name}' created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to create user. Please try again.']);
        }
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        $user->load(['role', 'studentProfile']);
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        DB::beginTransaction();
        try {
            $oldRole = $user->role?->name;

            $attributes = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Role assignment is a System Administrator privilege.
            if ($request->user()->isSystemAdmin() && $request->filled('role_id')) {
                $attributes['role_id'] = $request->role_id;
            }

            $user->update($attributes);

            $newRole = $user->fresh()->role?->name;

            // Handle student profile
            if ($user->isStudent()) {
                $profileData = $request->only([
                    'student_number', 'first_name', 'last_name', 'middle_name',
                    'contact_number', 'college', 'program', 'year_level',
                    'gwa', 'family_income', 'address',
                ]);

                if ($request->filled('enrollment_status')) {
                    $profileData['enrollment_status'] = $request->enrollment_status;
                }

                if ($user->studentProfile) {
                    $user->studentProfile->update($profileData);
                } elseif ($request->filled('student_number')) {
                    $profileData['user_id'] = $user->id;
                    $profileData['year_level'] = $profileData['year_level'] ?? 1;
                    StudentProfile::create($profileData);
                }
            }
            // Note: If role changes FROM student to another role, the existing
            // student profile is preserved (not deleted) for data integrity.

            DB::commit();

            $changes = [];
            if ($oldRole !== $newRole) {
                $changes[] = "Role changed from '{$oldRole}' to '{$newRole}'.";
            }
            $changes[] = 'User details updated.';

            AuditLogService::log(
                action: $oldRole !== $newRole ? 'User Role Changed' : 'User Updated',
                module: 'User Management',
                recordType: 'User',
                recordId: $user->id,
                description: implode(' ', $changes)." User: {$user->name} ({$user->email}).",
                request: $request
            );

            // Staff edits here also change eligibility inputs, so announce any
            // open scholarships the student now qualifies for.
            if ($user->isStudent() && $studentProfile = $user->studentProfile()->first()) {
                $this->announcements->announceToStudent($studentProfile);
            }

            return redirect()->route('admin.users.index')
                ->with('status', "User '{$user->name}' updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to update user. Please try again.']);
        }
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update(['is_active' => true]);

        AuditLogService::log(
            action: 'User Activated',
            module: 'User Management',
            recordType: 'User',
            recordId: $user->id,
            description: "Activated account for '{$user->name}' ({$user->email}).",
            request: $request
        );

        return redirect()->route('admin.users.index')
            ->with('status', "User '{$user->name}' has been activated.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $authUser = $request->user();

        // Self-deactivation prevention
        if ($user->id === $authUser->id) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        // The System Administrator account can only be managed by itself.
        if ($user->isSystemAdmin() && ! $authUser->isSystemAdmin()) {
            return back()->withErrors(['error' => 'Only the System Administrator can deactivate this account.']);
        }

        // Final active admin prevention
        if ($user->isAdmin()) {
            $activeAdminCount = User::where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))
                ->count();

            if ($activeAdminCount <= 1) {
                return back()->withErrors(['error' => 'Cannot deactivate the last active administrator account.']);
            }
        }

        $user->update(['is_active' => false]);

        AuditLogService::log(
            action: 'User Deactivated',
            module: 'User Management',
            recordType: 'User',
            recordId: $user->id,
            description: "Deactivated account for '{$user->name}' ({$user->email}).",
            request: $request
        );

        return redirect()->route('admin.users.index')
            ->with('status', "User '{$user->name}' has been deactivated.");
    }

    public function showResetPasswordForm(User $user): View
    {
        Gate::authorize('manage-users');

        if ($user->isSystemAdmin() && ! auth()->user()->isSystemAdmin()) {
            abort(403, 'Only the System Administrator can reset this account.');
        }

        return view('admin.users.reset-password', compact('user'));
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($user->isSystemAdmin() && ! $request->user()->isSystemAdmin()) {
            abort(403, 'Only the System Administrator can reset this account.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogService::log(
            action: 'Password Reset',
            module: 'User Management',
            recordType: 'User',
            recordId: $user->id,
            description: "Administrator reset password for '{$user->name}' ({$user->email}).",
            request: $request
        );

        return redirect()->route('admin.users.index')
            ->with('status', "Password for '{$user->name}' has been reset successfully.");
    }
}
