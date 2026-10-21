<?php

namespace App\DataObjects;

use App\Enums\EligibilityStatus;

class CriterionResult
{
    public function __construct(
        public readonly string $criterionType,
        public readonly string $criterionLabel,
        public readonly EligibilityStatus $status,
        public readonly string $explanation,
        public readonly bool $isMandatory,
        public readonly ?string $studentValueDisplay = null,
        public readonly ?string $requiredValueDisplay = null,
    ) {}

    public function toArray(): array
    {
        return [
            'criterion_type' => $this->criterionType,
            'criterion_label' => $this->criterionLabel,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'explanation' => $this->explanation,
            'is_mandatory' => $this->isMandatory,
            'student_value_display' => $this->studentValueDisplay,
            'required_value_display' => $this->requiredValueDisplay,
        ];
    }
}
