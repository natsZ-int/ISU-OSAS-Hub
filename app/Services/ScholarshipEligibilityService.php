<?php

namespace App\Services;

use App\DataObjects\CriterionResult;
use App\DataObjects\EligibilityEvaluationResult;
use App\Enums\EligibilityStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OverallEligibility;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;

class ScholarshipEligibilityService
{
    /**
     * Evaluate a student profile against a scholarship using
     * a deterministic, rule-based algorithm.
     *
     * Returns an explainable result containing per-criterion
     * outcomes and an overall eligibility decision.
     */
    public function evaluate(
        StudentProfile $studentProfile,
        Scholarship $scholarship,
        ?ScholarshipApplication $application = null
    ): EligibilityEvaluationResult {
        $criteria = [
            $this->evaluateGwa($studentProfile, $scholarship),
            $this->evaluateFamilyIncome($studentProfile, $scholarship),
            $this->evaluateProgram($studentProfile, $scholarship),
            $this->evaluateYearLevel($studentProfile, $scholarship),
            $this->evaluateEnrollmentStatus($studentProfile, $scholarship),
        ];

        $overall = $this->determineOverall($criteria);

        $reasonSummary = $overall === OverallEligibility::NOT_ELIGIBLE
            ? 'One or more mandatory scholarship eligibility criteria were not met.'
            : null;

        // Optional application-readiness info (documents)
        $applicationReadiness = [];
        if ($application) {
            $applicationReadiness = $this->evaluateApplicationReadiness($application);
        }

        return new EligibilityEvaluationResult(
            studentProfileId: $studentProfile->id,
            scholarshipId: $scholarship->id,
            overall: $overall,
            criteria: $criteria,
            reasonSummary: $reasonSummary,
            evaluatedAt: now(),
            applicationReadiness: $applicationReadiness,
        );
    }

    // ── Criterion Evaluators ─────────────────────────

    /**
     * Rule: student_gwa <= configured_maximum_gwa
     * (Lower GWA is better on the Philippine 1.00–5.00 scale)
     */
    protected function evaluateGwa(StudentProfile $profile, Scholarship $scholarship): CriterionResult
    {
        $required = $scholarship->minimum_gwa;

        if ($required === null) {
            return new CriterionResult(
                criterionType: 'minimum_gwa',
                criterionLabel: 'Academic Requirement (GWA)',
                status: EligibilityStatus::NOT_APPLICABLE,
                explanation: 'This scholarship does not configure a GWA requirement.',
                isMandatory: false,
            );
        }

        if ($profile->gwa === null) {
            return new CriterionResult(
                criterionType: 'minimum_gwa',
                criterionLabel: 'Academic Requirement (GWA)',
                status: EligibilityStatus::NOT_MET,
                explanation: 'Your GWA is not available in your student profile. Update your profile or contact the administrator.',
                isMandatory: true,
                requiredValueDisplay: (string) $required,
            );
        }

        $studentGwa = (float) $profile->gwa;
        $requiredGwa = (float) $required;
        $met = $studentGwa <= $requiredGwa;

        return new CriterionResult(
            criterionType: 'minimum_gwa',
            criterionLabel: 'Academic Requirement (GWA)',
            status: $met ? EligibilityStatus::MET : EligibilityStatus::NOT_MET,
            explanation: $met
                ? "Your GWA of {$studentGwa} meets the required GWA of {$requiredGwa} or better."
                : "Your GWA of {$studentGwa} does not meet the required GWA of {$requiredGwa} or better.",
            isMandatory: true,
            studentValueDisplay: (string) $studentGwa,
            requiredValueDisplay: (string) $requiredGwa,
        );
    }

    /**
     * Rule: student_family_income <= configured_maximum_family_income
     */
    protected function evaluateFamilyIncome(StudentProfile $profile, Scholarship $scholarship): CriterionResult
    {
        $required = $scholarship->maximum_family_income;

        if ($required === null) {
            return new CriterionResult(
                criterionType: 'maximum_family_income',
                criterionLabel: 'Family Income Requirement',
                status: EligibilityStatus::NOT_APPLICABLE,
                explanation: 'This scholarship does not configure a family income limit.',
                isMandatory: false,
            );
        }

        if ($profile->family_income === null) {
            return new CriterionResult(
                criterionType: 'maximum_family_income',
                criterionLabel: 'Family Income Requirement',
                status: EligibilityStatus::NOT_MET,
                explanation: 'Your family income is not available in your student profile. Update your profile or contact the administrator.',
                isMandatory: true,
                requiredValueDisplay: '₱' . number_format((float) $required, 2),
            );
        }

        $studentIncome = (float) $profile->family_income;
        $requiredIncome = (float) $required;
        $met = $studentIncome <= $requiredIncome;

        return new CriterionResult(
            criterionType: 'maximum_family_income',
            criterionLabel: 'Family Income Requirement',
            status: $met ? EligibilityStatus::MET : EligibilityStatus::NOT_MET,
            explanation: $met
                ? 'Your reported family income is within the configured limit.'
                : 'Your reported family income exceeds the configured limit.',
            isMandatory: true,
            studentValueDisplay: '₱' . number_format($studentIncome, 2),
            requiredValueDisplay: '₱' . number_format($requiredIncome, 2),
        );
    }

    /**
     * Rule: normalized_student_program in normalized_eligible_programs
     */
    protected function evaluateProgram(StudentProfile $profile, Scholarship $scholarship): CriterionResult
    {
        $eligiblePrograms = $scholarship->eligible_programs;

        if (empty($eligiblePrograms)) {
            return new CriterionResult(
                criterionType: 'eligible_programs',
                criterionLabel: 'Program Requirement',
                status: EligibilityStatus::NOT_APPLICABLE,
                explanation: 'This scholarship is open to all programs.',
                isMandatory: false,
            );
        }

        $requiredDisplay = implode(', ', $eligiblePrograms);

        if (blank($profile->program)) {
            return new CriterionResult(
                criterionType: 'eligible_programs',
                criterionLabel: 'Program Requirement',
                status: EligibilityStatus::NOT_MET,
                explanation: 'Your program is not set in your student profile. Update your profile or contact the administrator.',
                isMandatory: true,
                requiredValueDisplay: $requiredDisplay,
            );
        }

        // Normalize for comparison but preserve display values
        $studentProgram = strtoupper(trim($profile->program));
        $normalized = array_map(fn ($p) => strtoupper(trim($p)), $eligiblePrograms);
        $met = in_array($studentProgram, $normalized, true);

        return new CriterionResult(
            criterionType: 'eligible_programs',
            criterionLabel: 'Program Requirement',
            status: $met ? EligibilityStatus::MET : EligibilityStatus::NOT_MET,
            explanation: $met
                ? 'Your program is included in the scholarship\'s eligible programs.'
                : 'Your program is not among the scholarship\'s eligible programs.',
            isMandatory: true,
            studentValueDisplay: $profile->program,
            requiredValueDisplay: $requiredDisplay,
        );
    }

    /**
     * Rule: student_year_level in configured_eligible_year_levels
     */
    protected function evaluateYearLevel(StudentProfile $profile, Scholarship $scholarship): CriterionResult
    {
        $eligibleLevels = $scholarship->eligible_year_levels;

        if (empty($eligibleLevels)) {
            return new CriterionResult(
                criterionType: 'eligible_year_levels',
                criterionLabel: 'Year-Level Requirement',
                status: EligibilityStatus::NOT_APPLICABLE,
                explanation: 'This scholarship is open to all year levels.',
                isMandatory: false,
            );
        }

        $requiredDisplay = implode(', ', $eligibleLevels);

        if ($profile->year_level === null) {
            return new CriterionResult(
                criterionType: 'eligible_year_levels',
                criterionLabel: 'Year-Level Requirement',
                status: EligibilityStatus::NOT_MET,
                explanation: 'Your year level is not set in your student profile.',
                isMandatory: true,
                requiredValueDisplay: $requiredDisplay,
            );
        }

        $studentLevel = (int) $profile->year_level;
        $normalized = array_map('intval', $eligibleLevels);
        $met = in_array($studentLevel, $normalized, true);

        return new CriterionResult(
            criterionType: 'eligible_year_levels',
            criterionLabel: 'Year-Level Requirement',
            status: $met ? EligibilityStatus::MET : EligibilityStatus::NOT_MET,
            explanation: $met
                ? "Your year level ({$studentLevel}) is eligible."
                : "Your year level ({$studentLevel}) is not among the eligible year levels.",
            isMandatory: true,
            studentValueDisplay: (string) $studentLevel,
            requiredValueDisplay: $requiredDisplay,
        );
    }

    /**
     * Rule: student_enrollment_status equals required_enrollment_status
     */
    protected function evaluateEnrollmentStatus(StudentProfile $profile, Scholarship $scholarship): CriterionResult
    {
        $required = $scholarship->required_enrollment_status ?? null;

        if (blank($required)) {
            return new CriterionResult(
                criterionType: 'enrollment_status',
                criterionLabel: 'Enrollment Status',
                status: EligibilityStatus::NOT_APPLICABLE,
                explanation: 'This scholarship does not require a specific enrollment status.',
                isMandatory: false,
            );
        }

        $studentStatus = $profile->enrollment_status ?? EnrollmentStatus::UNKNOWN->value;

        if ($studentStatus === EnrollmentStatus::UNKNOWN->value) {
            return new CriterionResult(
                criterionType: 'enrollment_status',
                criterionLabel: 'Enrollment Status',
                status: EligibilityStatus::NOT_MET,
                explanation: 'Your enrollment status is not confirmed. Contact the administrator to verify your enrollment.',
                isMandatory: true,
                requiredValueDisplay: EnrollmentStatus::from($required)->label(),
            );
        }

        $met = $studentStatus === $required;

        return new CriterionResult(
            criterionType: 'enrollment_status',
            criterionLabel: 'Enrollment Status',
            status: $met ? EligibilityStatus::MET : EligibilityStatus::NOT_MET,
            explanation: $met
                ? 'Your enrollment status matches the scholarship requirement.'
                : 'Your enrollment status does not match the scholarship requirement.',
            isMandatory: true,
            studentValueDisplay: EnrollmentStatus::from($studentStatus)->label(),
            requiredValueDisplay: EnrollmentStatus::from($required)->label(),
        );
    }

    // ── Application Readiness (Documents) ────────────

    /**
     * Documents are evaluated separately from profile eligibility.
     * Missing documents do NOT make a student profile-ineligible;
     * they are shown as application readiness information.
     */
    protected function evaluateApplicationReadiness(ScholarshipApplication $application): array
    {
        $application->loadMissing(['scholarship.requirements', 'documents']);

        $requiredRequirements = $application->scholarship->requirements
            ->where('is_required', true);

        if ($requiredRequirements->isEmpty()) {
            return [
                'has_required_documents' => true,
                'missing'                => [],
                'pending'                => [],
                'rejected'               => [],
            ];
        }

        $uploadedDocs = $application->documents->keyBy('scholarship_requirement_id');
        $missing = [];
        $pending = [];
        $rejected = [];

        foreach ($requiredRequirements as $req) {
            $doc = $uploadedDocs->get($req->id);

            if (! $doc) {
                $missing[] = $req->requirement_name;
            } elseif ($doc->verification_status === 'pending') {
                $pending[] = $req->requirement_name;
            } elseif ($doc->verification_status === 'rejected') {
                $rejected[] = $req->requirement_name;
            }
        }

        return [
            'has_required_documents' => empty($missing),
            'missing'                => $missing,
            'pending'                => $pending,
            'rejected'               => $rejected,
        ];
    }

    // ── Overall Decision ─────────────────────────────

    /**
     * Overall Rule:
     *   Overall = ELIGIBLE  ⟺ every mandatory configured criterion is MET.
     *   Overall = NOT_ELIGIBLE otherwise.
     *
     * NOT_APPLICABLE criteria are ignored in the decision.
     *
     * @param  CriterionResult[]  $criteria
     */
    protected function determineOverall(array $criteria): OverallEligibility
    {
        foreach ($criteria as $c) {
            if ($c->isMandatory && $c->status === EligibilityStatus::NOT_MET) {
                return OverallEligibility::NOT_ELIGIBLE;
            }
        }

        return OverallEligibility::ELIGIBLE;
    }
}