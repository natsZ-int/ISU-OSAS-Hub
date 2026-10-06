@php
    use App\Enums\ScholarshipApplicationStatus;

    $stages = ScholarshipApplicationStatus::workflowOrder();
    $currentValue = $currentStatus?->value;
    $currentIndex = $currentStatus ? array_search($currentStatus, $stages) : -1;
    $isApproved = $currentStatus === ScholarshipApplicationStatus::APPROVED;
    $isRejected = $currentStatus === ScholarshipApplicationStatus::REJECTED;
@endphp

<div class="fsm-progress">
    <div class="fsm-steps">
        @foreach ($stages as $index => $stage)
            @php
                $isCompleted = $currentIndex > $index || $isApproved || ($isRejected && $currentIndex > $index);
                $isCurrent = $currentIndex === $index;
                $stepClass = $isCompleted ? 'fsm-step completed' : ($isCurrent ? 'fsm-step active' : 'fsm-step pending');
            @endphp
            <div class="{{ $stepClass }}">
                <div class="fsm-dot">
                    @if ($isCompleted) ✓ @else {{ $index + 1 }} @endif
                </div>
                <div class="fsm-label">{{ $stage->label() }}</div>
            </div>
            @if (! $loop->last)
                <div class="fsm-connector {{ $isCompleted ? 'completed' : '' }}"></div>
            @endif
        @endforeach

        {{-- Terminal branches --}}
        <div class="fsm-connector {{ $isApproved || $isRejected ? 'completed' : '' }}"></div>
        <div class="fsm-terminal">
            <div class="fsm-step {{ $isApproved ? 'completed approved' : 'pending' }}">
                <div class="fsm-dot">{{ $isApproved ? '✓' : '' }}</div>
                <div class="fsm-label">Approved</div>
            </div>
            <div class="fsm-step {{ $isRejected ? 'completed rejected' : 'pending' }}">
                <div class="fsm-dot">{{ $isRejected ? '✗' : '' }}</div>
                <div class="fsm-label">Rejected</div>
            </div>
        </div>
    </div>
</div>