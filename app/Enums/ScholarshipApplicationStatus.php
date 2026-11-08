<?php

namespace App\Enums;

enum ScholarshipApplicationStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case DOCUMENT_VERIFICATION = 'document_verification';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Human-readable label for Blade views.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::DOCUMENT_VERIFICATION => 'Document Verification',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Whether this state is a terminal (final) state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], true);
    }

    /**
     * The ordered list of FSM stages for the visual progress indicator.
     */
    public static function workflowOrder(): array
    {
        return [
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::DOCUMENT_VERIFICATION,
        ];
    }
}
