@props([
    'slices' => [],
    'centerValue' => null,
    'centerLabel' => 'Total',
    'emptyMessage' => 'No data available for the selected filters.',
])

@php
    $slices = collect($slices)->values();
    $total = (float) $slices->sum('value');

    $fmt = fn ($v) => ((float) $v == (int) $v) ? (string) (int) $v : number_format((float) $v, 1);

    $segments = [];

    if ($total > 0) {
        $size = 168;
        $stroke = 22;
        $radius = ($size - $stroke) / 2;
        $circumference = 2 * M_PI * $radius;
        $offset = 0;

        foreach ($slices as $slice) {
            $value = (float) ($slice['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }
            $fraction = $value / $total;
            $length = $fraction * $circumference;

            $segments[] = [
                'label'  => (string) ($slice['label'] ?? ''),
                'value'  => $value,
                'tone'   => (string) ($slice['tone'] ?? 'brand'),
                'dash'   => round($length, 3) . ' ' . round($circumference, 3),
                'offset' => round(-$offset, 3),
                'pct'    => round($fraction * 100, 1),
            ];

            $offset += $length;
        }
    }
@endphp

@if ($total <= 0 || empty($segments))
    <x-charts.chart-empty-state :message="$emptyMessage" />
@else
    <div class="donut-layout">
        <figure class="donut-figure">
            <svg
                class="donut-svg"
                viewBox="0 0 168 168"
                role="img"
                aria-label="Donut chart of {{ count($segments) }} categories. Total {{ $fmt($total) }}. {{ collect($segments)->map(fn ($s) => $s['label'] . ': ' . $fmt($s['value']) . ' (' . $s['pct'] . '%)')->implode('. ') }}."
            >
                <circle cx="84" cy="84" r="61" fill="none" stroke="var(--neutral-bg)" stroke-width="22"></circle>
                @foreach ($segments as $segment)
                    <circle
                        class="donut-seg tone-{{ $segment['tone'] }}"
                        cx="84"
                        cy="84"
                        r="61"
                        stroke-dasharray="{{ $segment['dash'] }}"
                        stroke-dashoffset="{{ $segment['offset'] }}"
                    >
                        <title>{{ $segment['label'] }}: {{ $fmt($segment['value']) }} ({{ $segment['pct'] }}%)</title>
                    </circle>
                @endforeach
            </svg>

            <div class="donut-center" aria-hidden="true">
                <strong>{{ $centerValue !== null ? $centerValue : $fmt($total) }}</strong>
                <span>{{ $centerLabel }}</span>
            </div>
        </figure>

        <div class="donut-legend">
            @foreach ($segments as $segment)
                <div class="legend-row">
                    <span class="legend-name">
                        <span class="chart-legend-swatch tone-{{ $segment['tone'] }}" aria-hidden="true"></span>
                        {{ $segment['label'] }}
                    </span>
                    <span class="legend-value">{{ $fmt($segment['value']) }} <small>({{ $segment['pct'] }}%)</small></span>
                </div>
            @endforeach
        </div>
    </div>

    <details class="chart-data">
        <summary>View data table</summary>
        <table>
            <thead>
                <tr>
                    <th scope="col">Category</th>
                    <th scope="col">Count</th>
                    <th scope="col">Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($segments as $segment)
                    <tr>
                        <td>{{ $segment['label'] }}</td>
                        <td>{{ $fmt($segment['value']) }}</td>
                        <td>{{ $segment['pct'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
@endif
