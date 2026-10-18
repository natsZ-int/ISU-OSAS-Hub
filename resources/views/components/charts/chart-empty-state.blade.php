@props([
    'title' => 'No data available.',
    'message' => 'No data matches the selected filters.',
])

<div class="chart-empty">
    <strong>{{ $title }}</strong>
    {{ $message }}
    {{ $slot }}
</div>
