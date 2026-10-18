@props([
    'labels' => [],
    'values' => [],
    'tones' => [],
    'max' => null,
    'showTable' => false,
    'emptyMessage' => 'No data available for the selected filters.',
])

@php
    $labels = collect($labels)->values();
    $values = collect($values)->values()->map(fn ($v) => (float) $v);
    $tones  = collect($tones)->values();
    $hasData = $labels->isNotEmpty() && $values->sum() > 0;
    $chartMax = $max ?: max([$values->max() ?: 0, 1]);
    $fmt = fn ($v) => ((float) $v == (int) $v) ? (string) (int) $v : number_format((float) $v, 1);
@endphp

@if (! $hasData)
    <x-charts.chart-empty-state :message="$emptyMessage" />
@else
    <div class="hbar-chart">
        @foreach ($labels as $i => $label)
            @php
                $value = $values[$i] ?? 0;
                $pct = $chartMax > 0 ? round(($value / $chartMax) * 100, 2) : 0;
                $tone = $tones[$i] ?? null;
            @endphp
            <div class="hbar-row">
                <span class="hbar-label" title="{{ $label }}">{{ $label }}</span>
                <div class="hbar-track" aria-hidden="true">
                    <div class="hbar-fill{{ $tone ? ' tone-' . $tone : '' }}" style="width: {{ $pct }}%"></div>
                </div>
                <span class="hbar-value">{{ $fmt($value) }}</span>
            </div>
        @endforeach
    </div>

    @if ($showTable)
        <details class="chart-data">
            <summary>View data table</summary>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Category</th>
                        <th scope="col">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($labels as $i => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $fmt($values[$i] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    @endif
@endif
