<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            // Log failed login attempt (no password stored)
            AuditLogService::log(
                action: 'failed_login',
                module: 'authentication',
                recordType: null,
                recordId: null,
                description: 'Failed login attempt.',
                userId: null,
                request: $request
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials.']);
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            AuditLogService::log(
                action: 'failed_login',
                module: 'authentication',
                recordType: 'User',
                recordId: $user->id,
                description: "Login blocked: account deactivated for '{$user->email}'.",
                userId: $user->id,
                request: $request
            );

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account has been deactivated. Contact the administrator.']);
        }

        $request->session()->regenerate();

        // Log successful login
        AuditLogService::log(
            action: 'login',
            module: 'authentication',
            recordType: 'User',
            recordId: $user->id,
            description: "User '{$user->name}' logged in successfully.",
            userId: $user->id,
            request: $request
        );

        return redirect()->intended($this->dashboardFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Log logout BEFORE invalidating the session
        if ($user) {
            AuditLogService::log(
                action: 'logout',
                module: 'authentication',
                recordType: 'User',
                recordId: $user->id,
                description: "User '{$user->name}' logged out.",
                userId: $user->id,
                request: $request
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function dashboardFor($user): string
    {
        return match (true) {
            $user->hasRole(Role::ADMIN) => route('admin.dashboard'),
            $user->hasRole(Role::COUNSELOR) => route('counselor.dashboard'),
            $user->hasRole(Role::STUDENT) => route('student.dashboard'),
            default => route('login'),
        };
    }
}
