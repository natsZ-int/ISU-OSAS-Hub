<?php

namespace App\Policies;

use App\Models\StudentServiceRequest;
use App\Models\User;

class StudentServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStudent();
    }

    public function view(User $user, StudentServiceRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $request->studentProfile && $request->studentProfile->user_id === $user->id;
        }

        if ($user->isCounselor()) {
            return $request->handled_by === $user->id;
        }

        return false;
    }

    public function cancel(User $user, StudentServiceRequest $request): bool
    {
        if ($request->isTerminal()) {
            return false;
        }

        if ($user->isStudent()) {
            return $request->studentProfile && $request->studentProfile->user_id === $user->id;
        }

        return $user->isAdmin() || ($request->handled_by === $user->id);
    }

    public function updateStatus(User $user, StudentServiceRequest $request): bool
    {
        if ($request->isTerminal()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCounselor() && $request->handled_by === $user->id;
    }

    public function assign(User $user, StudentServiceRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function downloadDocument(User $user, StudentServiceRequest $request): bool
    {
        return $this->view($user, $request);
    }
}