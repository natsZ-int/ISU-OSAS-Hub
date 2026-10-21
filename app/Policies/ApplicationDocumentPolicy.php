<?php

namespace App\Policies;

use App\Models\ApplicationDocument;
use App\Models\User;

class ApplicationDocumentPolicy
{
    public function view(User $user, ApplicationDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->studentProfile) {
            return $document->application->student_profile_id === $user->studentProfile->id;
        }

        return false;
    }
}
