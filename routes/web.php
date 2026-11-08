<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ApplicationReviewController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use App\Http\Controllers\Admin\GuidanceController as AdminGuidanceController;
use App\Http\Controllers\Admin\MatchingWeightController;
use App\Http\Controllers\Admin\RequirementController;
use App\Http\Controllers\Admin\ScholarshipController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Admin\ServiceTypeController as AdminServiceTypeController;
use App\Http\Controllers\Admin\StudentProfileController as AdminStudentProfileController;
use App\Http\Controllers\Admin\StudentProfileSecurityController as AdminStudentProfileSecurityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Counselor\GuidanceController as CounselorGuidanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff\EnrollmentVerificationController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\EligibilityController;
use App\Http\Controllers\Student\GuidanceController as StudentGuidanceController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ProfileSecurityController as StudentProfileSecurityController;
use App\Http\Controllers\Student\RecommendationController;
use App\Http\Controllers\Student\ScholarshipController as StudentScholarshipController;
use App\Http\Controllers\Student\ServiceRequestController as StudentServiceRequestController;
use App\Models\GuidanceAppointment;
use Illuminate\Support\Facades\Route;

// Explicit model binding for guidance appointments
Route::model('appointment', GuidanceAppointment::class);

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return match (true) {
        $user->isAdmin() => redirect()->route('admin.dashboard'),
        $user->isCounselor() => redirect()->route('counselor.dashboard'),
        $user->isStudent() => redirect()->route('student.dashboard'),
        default => abort(403, 'No dashboard is configured for this account role.'),
    };
});

// ─── Guest ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:3,1')->name('register.store');
});

// ─── Authenticated ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ─── Email Verification (OTP) ───────────────────────────────────
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify', [VerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/email/resend', [VerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.resend');

    // ─── Enrollment Verification (OSAS Staff: Admin + Counselor) ───
    Route::middleware('can:manage-enrollment-verifications')
        ->prefix('enrollment-verifications')
        ->name('enrollment-verifications.')
        ->group(function () {
            Route::get('/', [EnrollmentVerificationController::class, 'index'])->name('index');
            Route::get('/{enrollmentVerification}', [EnrollmentVerificationController::class, 'show'])->name('show');
            Route::patch('/{enrollmentVerification}', [EnrollmentVerificationController::class, 'update'])->name('update');
        });

    // ─── Admin ─────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        // Analytics (Admin Only, Active Account Required)
        Route::middleware('active')->prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/scholarships', [AnalyticsController::class, 'scholarships'])->name('scholarships');
            Route::get('/guidance', [AnalyticsController::class, 'guidance'])->name('guidance');
            Route::get('/student-services', [AnalyticsController::class, 'services'])->name('services');
            Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
        });

        // Matching Weights
        Route::get('/scholarship-matching/weights', [MatchingWeightController::class, 'edit'])->name('scholarship-matching.weights.edit');
        Route::put('/scholarship-matching/weights', [MatchingWeightController::class, 'update'])->name('scholarship-matching.weights.update');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::get('/users/{user}/reset-password', [UserController::class, 'showResetPasswordForm'])->name('users.reset-password');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password.update');

        // Student Profile Management
        Route::get('/student-profiles', [AdminStudentProfileController::class, 'index'])->name('student-profiles.index');
        Route::post('/student-profiles/{studentProfile}/unlock', [AdminStudentProfileSecurityController::class, 'unlock'])
            ->middleware('throttle:10,1')->name('student-profiles.unlock');
        Route::post('/student-profiles/{studentProfile}/lock', [AdminStudentProfileSecurityController::class, 'lock'])->name('student-profiles.lock');
        Route::put('/student-profiles/{studentProfile}/passcode', [AdminStudentProfileSecurityController::class, 'reset'])
            ->name('student-profiles.passcode.reset');
        Route::get('/student-profiles/{studentProfile}/edit', [AdminStudentProfileController::class, 'edit'])->name('student-profiles.edit');
        Route::put('/student-profiles/{studentProfile}', [AdminStudentProfileController::class, 'update'])->name('student-profiles.update');
        Route::get('/student-profiles/{studentProfile}', [AdminStudentProfileController::class, 'show'])->name('student-profiles.show');

        // Scholarship Management
        Route::get('/scholarships', [ScholarshipController::class, 'index'])->name('scholarships.index');
        Route::get('/scholarships/create', [ScholarshipController::class, 'create'])->name('scholarships.create');
        Route::post('/scholarships', [ScholarshipController::class, 'store'])->name('scholarships.store');
        Route::get('/scholarships/{scholarship}/edit', [ScholarshipController::class, 'edit'])->name('scholarships.edit');
        Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update'])->name('scholarships.update');
        Route::patch('/scholarships/{scholarship}/activate', [ScholarshipController::class, 'activate'])->name('scholarships.activate');
        Route::patch('/scholarships/{scholarship}/deactivate', [ScholarshipController::class, 'deactivate'])->name('scholarships.deactivate');
        Route::patch('/scholarships/{scholarship}/announce', [ScholarshipController::class, 'announceNow'])->name('scholarships.announce');

        // Requirements
        Route::get('/scholarships/{scholarship}/requirements', [RequirementController::class, 'index'])->name('scholarships.requirements.index');
        Route::post('/scholarships/{scholarship}/requirements', [RequirementController::class, 'store'])->name('scholarships.requirements.store');
        Route::put('/scholarships/{scholarship}/requirements/{scholarshipRequirement}', [RequirementController::class, 'update'])->name('scholarships.requirements.update');
        Route::delete('/scholarships/{scholarship}/requirements/{scholarshipRequirement}', [RequirementController::class, 'destroy'])->name('scholarships.requirements.destroy');

        // Application Review
        Route::get('/scholarship-applications', [ApplicationReviewController::class, 'index'])->name('scholarship-applications.index');
        Route::get('/scholarship-applications/{scholarshipApplication}', [ApplicationReviewController::class, 'show'])->name('scholarship-applications.show');
        Route::patch('/scholarship-applications/{scholarshipApplication}/transition', [ApplicationReviewController::class, 'transition'])->name('scholarship-applications.transition');

        // Document Verification
        Route::patch('/application-documents/{applicationDocument}/verification', [DocumentVerificationController::class, 'update'])->name('application-documents.update-verification');
        Route::get('/application-documents/{applicationDocument}/download', [DocumentController::class, 'download'])->name('application-documents.download');

        // Admin Guidance Oversight & Settings
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/appointment-types', [AdminGuidanceController::class, 'types'])->name('types');
            Route::post('/appointment-types', [AdminGuidanceController::class, 'storeType'])->name('types.store');
            Route::put('/appointment-types/{type}', [AdminGuidanceController::class, 'updateType'])->name('types.update');
            Route::patch('/appointment-types/{type}/toggle', [AdminGuidanceController::class, 'toggleType'])->name('types.toggle');
            Route::get('/scheduling-settings', [AdminGuidanceController::class, 'settings'])->name('settings');
            Route::patch('/scheduling-settings', [AdminGuidanceController::class, 'updateSettings'])->name('settings.update');
            Route::get('/appointments', [AdminGuidanceController::class, 'appointments'])->name('appointments');
        });

        // Student Services Management
        Route::get('/service-types', [AdminServiceTypeController::class, 'index'])->name('service-types.index');
        Route::get('/service-types/create', [AdminServiceTypeController::class, 'create'])->name('service-types.create');
        Route::post('/service-types', [AdminServiceTypeController::class, 'store'])->name('service-types.store');
        Route::get('/service-types/{service_type}/edit', [AdminServiceTypeController::class, 'edit'])->name('service-types.edit');
        Route::put('/service-types/{service_type}', [AdminServiceTypeController::class, 'update'])->name('service-types.update');
        Route::patch('/service-types/{service_type}/toggle', [AdminServiceTypeController::class, 'toggle'])->name('service-types.toggle');

        Route::get('/student-service-requests', [AdminServiceRequestController::class, 'index'])->name('student-service-requests.index');
        Route::get('/student-service-requests/{studentServiceRequest}', [AdminServiceRequestController::class, 'show'])->name('student-service-requests.show');
        Route::patch('/student-service-requests/{studentServiceRequest}/assign', [AdminServiceRequestController::class, 'assign'])->name('student-service-requests.assign');
        Route::patch('/student-service-requests/{studentServiceRequest}/transition', [AdminServiceRequestController::class, 'transition'])->name('student-service-requests.transition');
        Route::get('/student-service-requests/{studentServiceRequest}/documents/{serviceRequestDocument}/download', [AdminServiceRequestController::class, 'downloadDocument'])->name('student-service-requests.documents.download');

        // Audit Logs (Read-Only, Admin Only)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    // ─── Student ───────────────────────────────────────────────────────
    Route::middleware(['role:student', 'verified.email'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'student'])->name('dashboard');

        // Student Profile
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');

        // Student Profile Security (passcode protection)
        Route::get('/profile/security', [StudentProfileSecurityController::class, 'show'])->name('profile.security');
        Route::post('/profile/passcode', [StudentProfileSecurityController::class, 'store'])->name('profile.passcode.store');
        Route::put('/profile/passcode', [StudentProfileSecurityController::class, 'store'])->name('profile.passcode.update');
        Route::delete('/profile/passcode', [StudentProfileSecurityController::class, 'destroy'])->name('profile.passcode.destroy');
        Route::post('/profile/unlock', [StudentProfileSecurityController::class, 'unlock'])
            ->middleware('throttle:10,1')->name('profile.unlock');
        Route::post('/profile/lock', [StudentProfileSecurityController::class, 'lock'])->name('profile.lock');

        // Recommendations
        Route::get('/recommended-scholarships', [RecommendationController::class, 'index'])->name('scholarships.recommendations');
        Route::get('/recommended-scholarships/matches', [RecommendationController::class, 'matches'])->name('scholarships.matches');

        // Eligibility Preview
        Route::get('/scholarships/{scholarship}/eligibility', [EligibilityController::class, 'show'])->name('scholarships.eligibility');

        // Browse Scholarships
        Route::get('/scholarships', [StudentScholarshipController::class, 'index'])->name('scholarships.index');
        // Quick Search must be declared before the dynamic {scholarship} route.
        Route::get('/scholarships/quick-search', [StudentScholarshipController::class, 'quickSearch'])->name('scholarships.quick-search');
        Route::get('/scholarships/search', [StudentScholarshipController::class, 'search'])->name('scholarships.search');
        Route::get('/scholarships/{scholarship}', [StudentScholarshipController::class, 'show'])->name('scholarships.show');

        // Applications
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{scholarshipApplication}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/scholarships/{scholarship}/applications', [ApplicationController::class, 'store'])->middleware('enrollment.verified')->name('applications.store');
        Route::post('/applications/{scholarshipApplication}/submit', [ApplicationController::class, 'submit'])->middleware('enrollment.verified')->name('applications.submit');

        // Documents
        Route::post('/applications/{scholarshipApplication}/documents', [DocumentController::class, 'store'])->middleware('enrollment.verified')->name('documents.store');
        Route::delete('/applications/{scholarshipApplication}/documents/{applicationDocument}', [DocumentController::class, 'destroy'])->middleware('enrollment.verified')->name('documents.destroy');
        Route::get('/application-documents/{applicationDocument}/download', [DocumentController::class, 'download'])->name('documents.download');

        // Student Guidance Booking
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/counselors', [StudentGuidanceController::class, 'counselors'])->name('counselors');
            Route::get('/counselors/{counselor}/slots', [StudentGuidanceController::class, 'slots'])->name('slots');
            Route::get('/appointments', [StudentGuidanceController::class, 'appointments'])->name('appointments');
            Route::post('/appointments', [StudentGuidanceController::class, 'store'])->middleware('enrollment.verified')->name('appointments.store');
            Route::get('/appointments/{appointment}', [StudentGuidanceController::class, 'show'])->name('appointments.show');
            Route::patch('/appointments/{appointment}/cancel', [StudentGuidanceController::class, 'cancel'])->name('appointments.cancel');
        });

        // Student Services
        Route::get('/services', [StudentServiceRequestController::class, 'catalog'])->name('services.catalog');
        Route::get('/services/{serviceType}/apply', [StudentServiceRequestController::class, 'create'])->middleware('enrollment.verified')->name('services.create');
        Route::post('/service-requests', [StudentServiceRequestController::class, 'store'])->middleware('enrollment.verified')->name('services.store');
        Route::get('/service-requests', [StudentServiceRequestController::class, 'index'])->name('services.index');
        Route::get('/service-requests/{studentServiceRequest}', [StudentServiceRequestController::class, 'show'])->name('services.show');
        Route::patch('/service-requests/{studentServiceRequest}/cancel', [StudentServiceRequestController::class, 'cancel'])->name('services.cancel');
        Route::post('/service-requests/{studentServiceRequest}/documents', [StudentServiceRequestController::class, 'uploadAdditional'])->name('services.documents.store');
        Route::get('/service-requests/{studentServiceRequest}/documents/{serviceRequestDocument}/download', [StudentServiceRequestController::class, 'downloadDocument'])->name('services.documents.download');
    });

    // ─── Counselor ─────────────────────────────────────────────────────
    Route::middleware('role:counselor')->prefix('counselor')->name('counselor.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'counselor'])->name('dashboard');

        // Counselor Guidance Management
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/availability', [CounselorGuidanceController::class, 'availability'])->name('availability');
            Route::post('/availability', [CounselorGuidanceController::class, 'storeAvailability'])->name('availability.store');
            Route::patch('/availability/{availability}/deactivate', [CounselorGuidanceController::class, 'destroyAvailability'])->name('availability.deactivate');
            Route::get('/appointments', [CounselorGuidanceController::class, 'appointments'])->name('appointments');
            Route::get('/appointments/{appointment}', [CounselorGuidanceController::class, 'showAppointment'])->name('appointments.show');
            Route::patch('/appointments/{appointment}/confirm', [CounselorGuidanceController::class, 'confirm'])->name('appointments.confirm');
            Route::patch('/appointments/{appointment}/reject', [CounselorGuidanceController::class, 'reject'])->name('appointments.reject');
            Route::patch('/appointments/{appointment}/complete', [CounselorGuidanceController::class, 'complete'])->name('appointments.complete');
        });
    });

    // ─── Notifications (All Active Authenticated Users) ───────────
    Route::middleware('active')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });
});
