<?php

namespace App\Policies;

use App\Models\StudentServiceRequest;
use App\Models\User;

class StudentServiceRequestPolicy
{
    public function view(User $user, StudentServiceRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $request->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }

    public function cancel(User $user, StudentServiceRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $request->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }

    public function downloadDocument(User $user, StudentServiceRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $request->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }
}