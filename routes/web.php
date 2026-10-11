<?php

use App\Http\Controllers\Admin\ApplicationReviewController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use App\Http\Controllers\Admin\GuidanceController as AdminGuidanceController;
use App\Http\Controllers\Admin\MatchingWeightController;
use App\Http\Controllers\Admin\RequirementController;
use App\Http\Controllers\Admin\ScholarshipController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Admin\ServiceTypeController as AdminServiceTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Counselor\GuidanceController as CounselorGuidanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\EligibilityController;
use App\Http\Controllers\Student\GuidanceController as StudentGuidanceController;
use App\Http\Controllers\Student\RecommendationController;
use App\Http\Controllers\Student\ScholarshipController as StudentScholarshipController;
use App\Http\Controllers\Student\ServiceRequestController as StudentServiceRequestController;
use App\Models\GuidanceAppointment;
use Illuminate\Support\Facades\Route;

// Explicit model binding for guidance appointments
Route::model('appointment', GuidanceAppointment::class);

Route::get('/', fn () => redirect()->route('login'));

// ─── Guest ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

// ─── Authenticated ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        // ─── Admin ─────────────────────────────────────────────────────────
        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    
            // Analytics (Admin Only, Active Account Required)
        Route::middleware('active')->prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('/scholarships', [\App\Http\Controllers\Admin\AnalyticsController::class, 'scholarships'])->name('scholarships');
            Route::get('/guidance', [\App\Http\Controllers\Admin\AnalyticsController::class, 'guidance'])->name('guidance');
            Route::get('/student-services', [\App\Http\Controllers\Admin\AnalyticsController::class, 'services'])->name('services');
            Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
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

        // Scholarship Management
        Route::get('/scholarships', [ScholarshipController::class, 'index'])->name('scholarships.index');
        Route::get('/scholarships/create', [ScholarshipController::class, 'create'])->name('scholarships.create');
        Route::post('/scholarships', [ScholarshipController::class, 'store'])->name('scholarships.store');
        Route::get('/scholarships/{scholarship}/edit', [ScholarshipController::class, 'edit'])->name('scholarships.edit');
        Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update'])->name('scholarships.update');
        Route::patch('/scholarships/{scholarship}/activate', [ScholarshipController::class, 'activate'])->name('scholarships.activate');
        Route::patch('/scholarships/{scholarship}/deactivate', [ScholarshipController::class, 'deactivate'])->name('scholarships.deactivate');

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
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'student'])->name('dashboard');

        // Recommendations
        Route::get('/recommended-scholarships', [RecommendationController::class, 'index'])->name('scholarships.recommendations');

        // Eligibility Preview
        Route::get('/scholarships/{scholarship}/eligibility', [EligibilityController::class, 'show'])->name('scholarships.eligibility');

        // Browse Scholarships
        Route::get('/scholarships', [StudentScholarshipController::class, 'index'])->name('scholarships.index');
        Route::get('/scholarships/{scholarship}', [StudentScholarshipController::class, 'show'])->name('scholarships.show');

        // Applications
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{scholarshipApplication}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/scholarships/{scholarship}/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::post('/applications/{scholarshipApplication}/submit', [ApplicationController::class, 'submit'])->name('applications.submit');

        // Documents
        Route::post('/applications/{scholarshipApplication}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/applications/{scholarshipApplication}/documents/{applicationDocument}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/application-documents/{applicationDocument}/download', [DocumentController::class, 'download'])->name('documents.download');

        // Student Guidance Booking
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/counselors', [StudentGuidanceController::class, 'counselors'])->name('counselors');
            Route::get('/counselors/{counselor}/slots', [StudentGuidanceController::class, 'slots'])->name('slots');
            Route::get('/appointments', [StudentGuidanceController::class, 'appointments'])->name('appointments');
            Route::post('/appointments', [StudentGuidanceController::class, 'store'])->name('appointments.store');
            Route::get('/appointments/{appointment}', [StudentGuidanceController::class, 'show'])->name('appointments.show');
            Route::patch('/appointments/{appointment}/cancel', [StudentGuidanceController::class, 'cancel'])->name('appointments.cancel');
        });

        // Student Services
        Route::get('/services', [StudentServiceRequestController::class, 'catalog'])->name('services.catalog');
        Route::get('/services/{serviceType}/apply', [StudentServiceRequestController::class, 'create'])->name('services.create');
        Route::post('/service-requests', [StudentServiceRequestController::class, 'store'])->name('services.store');
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

    // ─── Notifications (All Authenticated Users) ───────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});