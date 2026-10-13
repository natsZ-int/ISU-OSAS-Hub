@props(['type' => 'info'])

@php
    $map = [
        'success' => ['class' => 'alert-success', 'role' => 'status'],
        'error'   => ['class' => 'alert-error',   'role' => 'alert'],
        'warning' => ['class' => 'alert-warning', 'role' => 'alert'],
        'info'    => ['class' => 'alert-info',    'role' => 'status'],
    ];
    $cfg = $map[$type] ?? $map['info'];
@endphp

<div class="alert {{ $cfg['class'] }}" role="{{ $cfg['role'] }}">
    {{ $slot }}
</div>
