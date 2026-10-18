<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtpVerificationService
{
    public const CODE_LENGTH        = 6;
    public const EXPIRY_MINUTES     = 5;
    public const MAX_ATTEMPTS       = 5;
    public const RESEND_COOLDOWN    = 60;

    /**
     * Issue a new six-digit OTP for the user.
     *
     * Any previously issued, unconsumed code is invalidated. Only a secure
     * hash of the code is persisted; the plaintext code is returned so the
     * caller can deliver it by email and is never stored or logged.
     */
    public function issue(User $user): string
    {
        $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $code) {
            OtpVerification::where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            OtpVerification::create([
                'user_id'    => $user->id,
                'email'      => $user->email,
                'code'       => Hash::make($code),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            ]);
        });

        return $code;
    }

    /**
     * Attempt to verify a submitted code against the latest active OTP.
     *
     * Returns true only when the code matches, has not expired, has not been
     * consumed, and the attempt limit has not been reached. Failed attempts
     * are counted; a successful attempt consumes the code.
     */
    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $record = OtpVerification::where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return false;
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                return false;
            }

            if (! Hash::check($code, $record->code)) {
                $record->increment('attempts');

                return false;
            }

            $record->update(['consumed_at' => now()]);

            return true;
        });
    }

    /**
     * Whether the resend cooldown has elapsed for the user.
     */
    public function canResend(User $user): bool
    {
        return $this->secondsUntilResend($user) === 0;
    }

    /**
     * Seconds remaining before another code may be requested (0 when ready).
     */
    public function secondsUntilResend(User $user): int
    {
        $latest = OtpVerification::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());

        return max(0, self::RESEND_COOLDOWN - $elapsed);
    }

    /**
     * Invalidate every unconsumed code for the user.
     */
    public function invalidatePrevious(User $user): void
    {
        OtpVerification::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }
}
