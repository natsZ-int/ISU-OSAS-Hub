@props(['status'])

@php
    $raw = match (true) {
        $status instanceof \BackedEnum => (string) $status->value,
        $status instanceof \UnitEnum => $status->name,
        is_object($status) && isset($status->value) => (string) $status->value,
        is_object($status) && method_exists($status, '__toString') => (string) $status,
        is_scalar($status) => (string) $status,
        default => '',
    };
    $key = strtolower(trim($raw));

    $map = [
        'draft'                            => ['label' => 'Draft',               'class' => 'neutral'],
        'submitted'                        => ['label' => 'Submitted',           'class' => 'info'],
        'under_review'                     => ['label' => 'Under Review',        'class' => 'warning'],
        'document_verification'            => ['label' => 'Verifying Documents', 'class' => 'info'],
        'approved'                         => ['label' => 'Approved',            'class' => 'success'],
        'rejected'                         => ['label' => 'Rejected',            'class' => 'danger'],
        'withdrawn'                        => ['label' => 'Withdrawn',           'class' => 'neutral'],

        'open'                             => ['label' => 'Open',                'class' => 'success'],
        'closed'                           => ['label' => 'Closed',              'class' => 'danger'],
        'archived'                         => ['label' => 'Archived',            'class' => 'neutral'],

        'pending'                          => ['label' => 'Pending',             'class' => 'warning'],
        'verified'                         => ['label' => 'Verified',            'class' => 'success'],

        'requested'                        => ['label' => 'Requested',           'class' => 'warning'],
        'confirmed'                        => ['label' => 'Confirmed',           'class' => 'info'],
        'processing'                       => ['label' => 'Processing',         'class' => 'info'],
        'completed'                        => ['label' => 'Completed',          'class' => 'success'],
        'cancelled'                        => ['label' => 'Cancelled',          'class' => 'neutral'],
        'requires_information'            => ['label' => 'Needs More Info',    'class' => 'warning'],

        'active'                           => ['label' => 'Active',             'class' => 'success'],
        'inactive'                         => ['label' => 'Inactive',           'class' => 'neutral'],
    ];

    $entry = $map[$key] ?? [
        'label' => \Illuminate\Support\Str::of($raw)->replace('_', ' ')->title()->toString(),
        'class' => 'neutral',
    ];
@endphp

<span class="badge badge-{{ $entry['class'] }}">{{ $entry['label'] }}</span>