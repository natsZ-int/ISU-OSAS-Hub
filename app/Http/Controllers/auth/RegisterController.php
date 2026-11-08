<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EnrollmentStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterStudentRequest;
use App\Mail\OtpVerificationMail;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\OtpVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private OtpVerificationService $otpService,
        private NotificationService $notificationService,
    ) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterStudentRequest $request): RedirectResponse
    {
        $studentRole = Role::where('name', Role::STUDENT)->first();

        if (! $studentRole) {
            return back()->withInput()->withErrors(['error' => 'Student registration is not available. Please contact the administrator.']);
        }

        DB::beginTransaction();
        try {
            $nameParts = preg_split('/\s+/', trim($request->name), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role_id' => $studentRole->id,
                'is_active' => true,
            ]);

            StudentProfile::create([
                'user_id' => $user->id,
                'student_number' => $request->student_number,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'college' => $request->college,
                'program' => $request->program,
                'contact_number' => $request->contact_number,
                'gwa' => $request->gwa,
                'family_income' => $request->family_income,
                'address' => $request->address,
                'year_level' => 1,
                'enrollment_status' => EnrollmentStatus::UNKNOWN->value,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Registration failed. Please try again.']);
        }

        $code = $this->otpService->issue($user);

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($user, $code));
        } catch (\Throwable $e) {
            Log::warning("OTP email delivery failed for user {$user->id}: {$e->getMessage()}");
            $this->otpService->invalidatePrevious($user);

            AuditLogService::log(
                action: 'otp_send_failed',
                module: 'authentication',
                recordType: 'User',
                recordId: $user->id,
                description: "Verification email could not be sent for '{$user->email}'.",
                userId: $user->id,
            );

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('verification.notice')
                ->with('error', 'Your account was created, but the verification email could not be sent. Please request a new code.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogService::log(
            action: 'student_registered',
            module: 'authentication',
            recordType: 'User',
            recordId: $user->id,
            description: "Student '{$user->name}' ({$user->email}) self-registered with student ID {$request->student_number}.",
            userId: $user->id,
            request: $request,
        );

        $admins = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))
            ->where('is_active', true)
            ->get();

        $this->notificationService->notifyMultiple(
            $admins->all(),
            NotificationType::ACCOUNT_REGISTERED,
            'New Student Registration',
            "{$user->name} ({$request->student_number}) created a student account and is awaiting email and enrollment verification.",
            'User',
            $user->id,
        );

        return redirect()->route('verification.notice')
            ->with('status', 'Your account has been created. We sent a six-digit verification code to your email address. It expires in '
                .OtpVerificationService::EXPIRY_MINUTES.' minutes.');
    }
}
