<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function view(User $user, StudentProfile $profile): bool
    {
        // Admins and counselors can view any profile
        if ($user->isAdmin() || $user->isCounselor()) {
            return true;
        }

        // Students can only view their own profile
        return $user->studentProfile && $user->studentProfile->id === $profile->id;
    }

    public function update(User $user, StudentProfile $profile): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->studentProfile && $user->studentProfile->id === $profile->id;
    }
}
