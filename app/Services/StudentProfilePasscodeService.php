<?php

namespace App\Services;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the optional passcode protection for sensitive student profile
 * information.
 *
 * Only a password hash is persisted. Verification always happens on the
 * backend with Hash::check(), and an unlocked profile is remembered in the
 * authenticated user's session for the current browser session only.
 */
class StudentProfilePasscodeService
{
    public const MIN_LENGTH = 4;

    public const MAX_LENGTH = 32;

    private const SESSION_PREFIX = 'student_profile_unlocked.';

    public function hasPasscode(StudentProfile $profile): bool
    {
        return filled($profile->profile_passcode_hash);
    }

    /**
     * Set (or replace) the passcode for a profile.
     */
    public function setPasscode(StudentProfile $profile, string $passcode): void
    {
        $profile->forceFill([
            'profile_passcode_hash' => Hash::make($passcode),
        ])->save();
    }

    public function clearPasscode(StudentProfile $profile, Request $request): void
    {
        $profile->forceFill(['profile_passcode_hash' => null])->save();

        $this->lock($profile, $request);
    }

    /**
     * Compare a candidate passcode against the stored hash.
     */
    public function verify(StudentProfile $profile, ?string $passcode): bool
    {
        if (! $this->hasPasscode($profile) || $passcode === null || $passcode === '') {
            return false;
        }

        return Hash::check($passcode, $profile->profile_passcode_hash);
    }

    /**
     * A profile without a passcode is treated as unlocked. Once a passcode is
     * configured, the viewer must unlock the profile for the session.
     */
    public function isUnlocked(StudentProfile $profile, ?Request $request = null): bool
    {
        if (! $this->hasPasscode($profile)) {
            return true;
        }

        $request ??= request();

        return $request->session()->get($this->sessionKey($profile), false) === true;
    }

    /**
     * Verify and mark the profile as unlocked for this session.
     */
    public function attemptUnlock(StudentProfile $profile, string $passcode, Request $request): bool
    {
        if (! $this->verify($profile, $passcode)) {
            return false;
        }

        $request->session()->put($this->sessionKey($profile), true);

        return true;
    }

    public function unlock(StudentProfile $profile, Request $request): void
    {
        $request->session()->put($this->sessionKey($profile), true);
    }

    public function lock(StudentProfile $profile, Request $request): void
    {
        $request->session()->forget($this->sessionKey($profile));
    }

    public function sessionKey(StudentProfile $profile): string
    {
        return self::SESSION_PREFIX.$profile->getKey();
    }
}
