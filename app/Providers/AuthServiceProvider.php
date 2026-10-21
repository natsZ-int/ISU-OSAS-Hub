<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-users', function ($user) {
            return $user->isAdmin() && $user->isActive();
        });

        Gate::define('access-admin-panel', function ($user) {
            return $user->isAdmin() && $user->isActive();
        });

        Gate::define('access-counselor-panel', function ($user) {
            return $user->isCounselor() && $user->isActive();
        });

        Gate::define('access-student-panel', function ($user) {
            return $user->isStudent() && $user->isActive();
        });

        Gate::define('manage-enrollment-verifications', function ($user) {
            return ($user->isAdmin() || $user->isCounselor()) && $user->isActive();
        });
    }
}
