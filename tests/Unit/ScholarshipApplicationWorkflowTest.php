<?php

namespace Tests\Unit;

use App\Enums\ScholarshipApplicationStatus;
use App\Services\ScholarshipApplicationWorkflowService;
use PHPUnit\Framework\TestCase;

class ScholarshipApplicationWorkflowTest extends TestCase
{
    private ScholarshipApplicationWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = new ScholarshipApplicationWorkflowService();
    }

    // ── Valid Transitions ────────────────────────────

    public function test_submitted_can_transition_to_under_review(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('submitted');
        $this->assertContains(ScholarshipApplicationStatus::UNDER_REVIEW, $allowed);
    }

    public function test_under_review_can_transition_to_document_verification(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('under_review');
        $this->assertContains(ScholarshipApplicationStatus::DOCUMENT_VERIFICATION, $allowed);
    }

    public function test_under_review_can_transition_to_rejected(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('under_review');
        $this->assertContains(ScholarshipApplicationStatus::REJECTED, $allowed);
    }

    public function test_document_verification_can_transition_to_approved(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('document_verification');
        $this->assertContains(ScholarshipApplicationStatus::APPROVED, $allowed);
    }

    public function test_document_verification_can_transition_to_rejected(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('document_verification');
        $this->assertContains(ScholarshipApplicationStatus::REJECTED, $allowed);
    }

    // ── Invalid Transitions ──────────────────────────

    public function test_submitted_cannot_transition_to_approved(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('submitted');
        $this->assertNotContains(ScholarshipApplicationStatus::APPROVED, $allowed);
    }

    public function test_submitted_cannot_transition_to_rejected(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('submitted');
        $this->assertNotContains(ScholarshipApplicationStatus::REJECTED, $allowed);
    }

    public function test_submitted_cannot_transition_to_document_verification(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('submitted');
        $this->assertNotContains(ScholarshipApplicationStatus::DOCUMENT_VERIFICATION, $allowed);
    }

    public function test_under_review_cannot_transition_to_approved(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('under_review');
        $this->assertNotContains(ScholarshipApplicationStatus::APPROVED, $allowed);
    }

    public function test_approved_has_no_transitions(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('approved');
        $this->assertEmpty($allowed);
    }

    public function test_rejected_has_no_transitions(): void
    {
        $allowed = $this->workflow->getAllowedTransitions('rejected');
        $this->assertEmpty($allowed);
    }

    // ── Terminal States ──────────────────────────────

    public function test_approved_is_terminal(): void
    {
        $this->assertTrue(ScholarshipApplicationStatus::APPROVED->isTerminal());
    }

    public function test_rejected_is_terminal(): void
    {
        $this->assertTrue(ScholarshipApplicationStatus::REJECTED->isTerminal());
    }

    public function test_submitted_is_not_terminal(): void
    {
        $this->assertFalse(ScholarshipApplicationStatus::SUBMITTED->isTerminal());
    }
}