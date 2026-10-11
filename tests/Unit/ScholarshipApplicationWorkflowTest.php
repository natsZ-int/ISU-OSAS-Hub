<?php

namespace Tests\Unit;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ScholarshipApplicationWorkflowService;
use PHPUnit\Framework\TestCase;

class ScholarshipApplicationWorkflowTest extends TestCase
{
    protected ScholarshipApplicationWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the required NotificationService dependency
        $notificationServiceMock = $this->createMock(NotificationService::class);

        $this->workflowService = new ScholarshipApplicationWorkflowService($notificationServiceMock);
    }

    public function test_submitted_can_transition_to_under_review()
    {
        $application = new ScholarshipApplication(['status' => 'submitted']);
        $actor = new User();
        $actor->role_id = 1; // Assuming Admin/Appropriate Actor Role

        $this->assertTrue(
            $this->workflowService->canTransition($application, 'under_review', $actor)
        );
    }

    public function test_under_review_can_transition_to_document_verification()
    {
        $application = new ScholarshipApplication(['status' => 'under_review']);
        $actor = new User();

        $this->assertTrue(
            $this->workflowService->canTransition($application, 'document_verification', $actor)
        );
    }

    public function test_under_review_can_transition_to_rejected()
    {
        $application = new ScholarshipApplication(['status' => 'under_review']);
        $actor = new User();

        $this->assertTrue(
            $this->workflowService->canTransition($application, 'rejected', $actor)
        );
    }

    public function test_document_verification_can_transition_to_approved()
    {
        $application = new ScholarshipApplication(['status' => 'document_verification']);
        $actor = new User();

        $this->assertTrue(
            $this->workflowService->canTransition($application, 'approved', $actor)
        );
    }

    public function test_document_verification_can_transition_to_rejected()
    {
        $application = new ScholarshipApplication(['status' => 'document_verification']);
        $actor = new User();

        $this->assertTrue(
            $this->workflowService->canTransition($application, 'rejected', $actor)
        );
    }

    public function test_submitted_cannot_transition_to_approved()
    {
        $application = new ScholarshipApplication(['status' => 'submitted']);
        $actor = new User();

        $this->assertFalse(
            $this->workflowService->canTransition($application, 'approved', $actor)
        );
    }

    public function test_submitted_cannot_transition_to_rejected()
    {
        $application = new ScholarshipApplication(['status' => 'submitted']);
        $actor = new User();

        $this->assertFalse(
            $this->workflowService->canTransition($application, 'rejected', $actor)
        );
    }

    public function test_submitted_cannot_transition_to_document_verification()
    {
        $application = new ScholarshipApplication(['status' => 'submitted']);
        $actor = new User();

        $this->assertFalse(
            $this->workflowService->canTransition($application, 'document_verification', $actor)
        );
    }

    public function test_under_review_cannot_transition_to_approved()
    {
        $application = new ScholarshipApplication(['status' => 'under_review']);
        $actor = new User();

        $this->assertFalse(
            $this->workflowService->canTransition($application, 'approved', $actor)
        );
    }

    public function test_approved_has_no_transitions()
    {
        $application = new ScholarshipApplication(['status' => 'approved']);
        $this->assertEmpty($this->workflowService->getAllowedTransitions($application));
    }

    public function test_rejected_has_no_transitions()
    {
        $application = new ScholarshipApplication(['status' => 'rejected']);
        $this->assertEmpty($this->workflowService->getAllowedTransitions($application));
    }

    public function test_approved_is_terminal()
    {
        $status = ScholarshipApplicationStatus::APPROVED;
        $this->assertTrue($status->isTerminal());
    }

    public function test_rejected_is_terminal()
    {
        $status = ScholarshipApplicationStatus::REJECTED;
        $this->assertTrue($status->isTerminal());
    }

    public function test_submitted_is_not_terminal()
    {
        $status = ScholarshipApplicationStatus::SUBMITTED;
        $this->assertFalse($status->isTerminal());
    }
}