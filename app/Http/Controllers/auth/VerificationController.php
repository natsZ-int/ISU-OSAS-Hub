<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EnrollmentVerificationStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\Role;
use App\Models\StudentEnrollmentVerification;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\OtpVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(
        private OtpVerificationService $otpService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Show the email verification (OTP entry) page.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('student.dashboard');
        }

        $cooldown = $this->otpService->secondsUntilResend($user);

        return view('auth.verify-otp', [
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * Verify the submitted six-digit code.
     */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'code.size' => 'The verification code must be exactly six digits.',
            'code.regex' => 'The verification code must contain digits only.',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('student.dashboard');
        }

        $success = $this->otpService->verify($user, $validated['code']);

        if (! $success) {
            AuditLogService::log(
                action: 'otp_verify_failed',
                module: 'authentication',
                recordType: 'User',
                recordId: $user->id,
                description: "Failed email verification attempt for '{$user->email}'.",
                userId: $user->id,
                request: $request,
            );

            return back()->withErrors(['code' => 'The verification code is incorrect or has expired.']);
        }

        DB::transaction(function () use ($user) {
            $user->markEmailAsVerified();

            $profile = $user->studentProfile;

            if ($profile) {
                $exists = StudentEnrollmentVerification::where('student_profile_id', $profile->id)
                    ->where('academic_year', config('academic.current_year'))
                    ->where('semester', config('academic.current_semester'))
                    ->exists();

                if (! $exists) {
                    StudentEnrollmentVerification::create([
                        'student_profile_id' => $profile->id,
                        'academic_year' => config('academic.current_year'),
                        'semester' => config('academic.current_semester'),
                        'status' => EnrollmentVerificationStatus::PENDING->value,
                    ]);
                }
            }
        });

        AuditLogService::log(
            action: 'email_verified',
            module: 'authentication',
            recordType: 'User',
            recordId: $user->id,
            description: "Email address '{$user->email}' verified via OTP.",
            userId: $user->id,
            request: $request,
        );

        $this->notificationService->notifyUser(
            $user,
            NotificationType::ACCOUNT_EMAIL_VERIFIED,
            'Email Verified',
            'Your email address has been verified. Your enrollment verification is now pending review by OSAS.',
        );

        $admins = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))
            ->where('is_active', true)
            ->get();

        $this->notificationService->notifyMultiple(
            $admins->all(),
            NotificationType::ACCOUNT_REGISTERED,
            'Enrollment Verification Pending',
            "{$user->name}'s email has been verified. Enrollment verification for "
            .config('academic.current_year').' '.config('academic.current_semester')
            .' is awaiting review.',
            'User',
            $user->id,
        );

        return redirect()->route('student.dashboard')
            ->with('status', 'Your email has been verified. Your enrollment verification is now pending review by OSAS.');
    }

    /**
     * Resend a new verification code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('student.dashboard');
        }

        if (! $this->otpService->canResend($user)) {
            return back()->withErrors([
                'resend' => 'Please wait '.$this->otpService->secondsUntilResend($user)
                    .' seconds before requesting a new code.',
            ]);
        }

        $code = $this->otpService->issue($user);

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($user, $code));
        } catch (\Throwable $e) {
            Log::warning("OTP resend email delivery failed for user {$user->id}: {$e->getMessage()}");
            $this->otpService->invalidatePrevious($user);

            return back()->withErrors(['resend' => 'The verification email could not be sent. Please try again shortly.']);
        }

        AuditLogService::log(
            action: 'otp_resent',
            module: 'authentication',
            recordType: 'User',
            recordId: $user->id,
            description: "A new verification code was sent to '{$user->email}'.",
            userId: $user->id,
            request: $request,
        );

        return back()->with('status', 'A new verification code has been sent to your email address.');
    }
}
