@props(['status'])

@php
    $raw = is_object($status) && method_exists($status, 'value') ? $status->value : (string) $status;
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
        'requires_additional_information'  => ['label' => 'Needs More Info',    'class' => 'warning'],

        'active'                           => ['label' => 'Active',             'class' => 'success'],
        'inactive'                         => ['label' => 'Inactive',           'class' => 'neutral'],
    ];

    $entry = $map[$key] ?? [
        'label' => \Illuminate\Support\Str::of($raw)->replace('_', ' ')->title()->toString(),
        'class' => 'neutral',
    ];
@endphp

<span class="badge badge-{{ $entry['class'] }}">{{ $entry['label'] }}</span>