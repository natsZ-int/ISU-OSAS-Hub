<?php

namespace App\Policies;

use App\Models\GuidanceAppointment;
use App\Models\User;

class GuidanceAppointmentPolicy
{
    public function view(User $user, GuidanceAppointment $appointment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCounselor()) {
            return $appointment->counselor_id === $user->id;
        }

        if ($user->studentProfile) {
            return $appointment->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }
}