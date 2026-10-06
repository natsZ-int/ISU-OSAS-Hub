<?php

namespace App\Enums;

enum NotificationType: string
{
    // Scholarship
    case SCHOLARSHIP_APPLICATION_SUBMITTED    = 'scholarship.application_submitted';
    case SCHOLARSHIP_APPLICATION_UNDER_REVIEW = 'scholarship.application_under_review';
    case SCHOLARSHIP_DOCUMENT_VERIFICATION    = 'scholarship.document_verification';
    case SCHOLARSHIP_APPLICATION_APPROVED     = 'scholarship.application_approved';
    case SCHOLARSHIP_APPLICATION_REJECTED     = 'scholarship.application_rejected';

    // Guidance
    case GUIDANCE_APPOINTMENT_REQUESTED  = 'guidance.appointment_requested';
    case GUIDANCE_APPOINTMENT_CONFIRMED  = 'guidance.appointment_confirmed';
    case GUIDANCE_APPOINTMENT_REJECTED   = 'guidance.appointment_rejected';
    case GUIDANCE_APPOINTMENT_CANCELLED  = 'guidance.appointment_cancelled';
    case GUIDANCE_APPOINTMENT_COMPLETED  = 'guidance.appointment_completed';

    // Student Services
    case STUDENT_SERVICE_REQUEST_SUBMITTED             = 'student_services.request_submitted';
    case STUDENT_SERVICE_REQUEST_PROCESSING            = 'student_services.request_processing';
    case STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED      = 'student_services.additional_information_required';
    case STUDENT_SERVICE_REQUEST_COMPLETED             = 'student_services.request_completed';
    case STUDENT_SERVICE_REQUEST_CANCELLED             = 'student_services.request_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SCHOLARSHIP_APPLICATION_SUBMITTED    => 'Application Submitted',
            self::SCHOLARSHIP_APPLICATION_UNDER_REVIEW => 'Application Under Review',
            self::SCHOLARSHIP_DOCUMENT_VERIFICATION    => 'Document Verification',
            self::SCHOLARSHIP_APPLICATION_APPROVED     => 'Application Approved',
            self::SCHOLARSHIP_APPLICATION_REJECTED     => 'Application Rejected',
            self::GUIDANCE_APPOINTMENT_REQUESTED       => 'Appointment Requested',
            self::GUIDANCE_APPOINTMENT_CONFIRMED       => 'Appointment Confirmed',
            self::GUIDANCE_APPOINTMENT_REJECTED        => 'Appointment Rejected',
            self::GUIDANCE_APPOINTMENT_CANCELLED       => 'Appointment Cancelled',
            self::GUIDANCE_APPOINTMENT_COMPLETED       => 'Appointment Completed',
            self::STUDENT_SERVICE_REQUEST_SUBMITTED    => 'Request Submitted',
            self::STUDENT_SERVICE_REQUEST_PROCESSING   => 'Request Processing',
            self::STUDENT_SERVICE_ADDITIONAL_INFO_REQUIRED => 'Information Required',
            self::STUDENT_SERVICE_REQUEST_COMPLETED    => 'Request Completed',
            self::STUDENT_SERVICE_REQUEST_CANCELLED    => 'Request Cancelled',
        };
    }

    public function category(): string
    {
        return match (true) {
            str_starts_with($this->value, 'scholarship.')      => 'Scholarship',
            str_starts_with($this->value, 'guidance.')         => 'Guidance',
            str_starts_with($this->value, 'student_services.') => 'Student Services',
            default                                            => 'General',
        };
    }
}