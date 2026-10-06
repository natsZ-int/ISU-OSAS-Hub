<?php

namespace App\Policies;

use App\Models\ScholarshipApplication;
use App\Models\User;

class ScholarshipApplicationPolicy
{
    public function view(User $user, ScholarshipApplication $application): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $application->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }

    public function update(User $user, ScholarshipApplication $application): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $application->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }
}