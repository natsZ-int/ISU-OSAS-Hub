<?php

namespace App\Enums;

enum NotificationType: string
{
    // Scholarship
    case SCHOLARSHIP_APPLICATION_SUBMITTED = 'scholarship.application_submitted';
    case SCHOLARSHIP_APPLICATION_UNDER_REVIEW = 'scholarship.application_under_review';
    case SCHOLARSHIP_DOCUMENT_VERIFICATION = 'scholarship.document_verification';
    case SCHOLARSHIP_DOCUMENT_APPROVED = 'scholarship.document_approved';
    case SCHOLARSHIP_DOCUMENT_REJECTED = 'scholarship.document_rejected';
    case SCHOLARSHIP_APPLICATION_APPROVED = 'scholarship.application_approved';
    case SCHOLARSHIP_APPLICATION_REJECTED = 'scholarship.application_rejected';
    case SCHOLARSHIP_ANNOUNCEMENT = 'scholarship.announcement';

    // Guidance
    case GUIDANCE_APPOINTMENT_REQUESTED = 'guidance.appointment_requested';
    case GUIDANCE_APPOINTMENT_CONFIRMED = 'guidance.appointment_confirmed';
    case GUIDANCE_APPOINTMENT_REJECTED = 'guidance.appointment_rejected';
    case GUIDANCE_APPOINTMENT_CANCELLED = 'guidance.appointment_cancelled';
    case GUIDANCE_APPOINTMENT_COMPLETED = 'guidance.appointment_completed';
    case GUIDANCE_APPOINTMENT_REMINDER = 'guidance.appointment_reminder';

    // Student Services
    case STUDENT_SERVICE_REQUEST_SUBMITTED = 'student_services.request_submitted';
    case STUDENT_SERVICE_REQUEST_PROCESSING = 'student_services.request_processing';
    case STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED = 'student_services.additional_information_required';
    case STUDENT_SERVICE_REQUEST_COMPLETED = 'student_services.request_completed';
    case STUDENT_SERVICE_REQUEST_CANCELLED = 'student_services.request_cancelled';

    // Account / Verification
    case ACCOUNT_REGISTERED = 'account.registered';
    case ACCOUNT_EMAIL_VERIFIED = 'account.email_verified';
    case ACCOUNT_ENROLLMENT_VERIFIED = 'account.enrollment_verified';
    case ACCOUNT_ENROLLMENT_REJECTED = 'account.enrollment_rejected';
    case ACCOUNT_ENROLLMENT_UNABLE_TO_VERIFY = 'account.unable_to_verify';

    public function label(): string
    {
        return match ($this) {
            self::SCHOLARSHIP_APPLICATION_SUBMITTED => 'Application Submitted',
            self::SCHOLARSHIP_APPLICATION_UNDER_REVIEW => 'Application Under Review',
            self::SCHOLARSHIP_DOCUMENT_VERIFICATION => 'Document Verification',
            self::SCHOLARSHIP_DOCUMENT_APPROVED => 'Document Accepted',
            self::SCHOLARSHIP_DOCUMENT_REJECTED => 'Document Rejected',
            self::SCHOLARSHIP_APPLICATION_APPROVED => 'Application Approved',
            self::SCHOLARSHIP_APPLICATION_REJECTED => 'Application Rejected',
            self::SCHOLARSHIP_ANNOUNCEMENT => 'New Scholarship',
            self::GUIDANCE_APPOINTMENT_REQUESTED => 'Appointment Requested',
            self::GUIDANCE_APPOINTMENT_CONFIRMED => 'Appointment Confirmed',
            self::GUIDANCE_APPOINTMENT_REJECTED => 'Appointment Rejected',
            self::GUIDANCE_APPOINTMENT_CANCELLED => 'Appointment Cancelled',
            self::GUIDANCE_APPOINTMENT_COMPLETED => 'Appointment Completed',
            self::GUIDANCE_APPOINTMENT_REMINDER => 'Appointment Reminder',
            self::STUDENT_SERVICE_REQUEST_SUBMITTED => 'Request Submitted',
            self::STUDENT_SERVICE_REQUEST_PROCESSING => 'Request Processing',
            self::STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED => 'Information Required',
            self::STUDENT_SERVICE_REQUEST_COMPLETED => 'Request Completed',
            self::STUDENT_SERVICE_REQUEST_CANCELLED => 'Request Cancelled',
            self::ACCOUNT_REGISTERED => 'Account Registered',
            self::ACCOUNT_EMAIL_VERIFIED => 'Email Verified',
            self::ACCOUNT_ENROLLMENT_VERIFIED => 'Enrollment Verified',
            self::ACCOUNT_ENROLLMENT_REJECTED => 'Enrollment Rejected',
            self::ACCOUNT_ENROLLMENT_UNABLE_TO_VERIFY => 'Unable to Verify Enrollment',
        };
    }

    public function category(): string
    {
        return match (true) {
            str_starts_with($this->value, 'scholarship.') => 'Scholarship',
            str_starts_with($this->value, 'guidance.') => 'Guidance',
            str_starts_with($this->value, 'student_services.') => 'Student Services',
            str_starts_with($this->value, 'account.') => 'Account',
            default => 'General',
        };
    }
}
