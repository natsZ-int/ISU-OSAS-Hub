@props([
    'points' => [],
    'height' => 210,
    'yMax' => null,
    'valueLabel' => 'Count',
    'emptyMessage' => 'No data available for the selected filters.',
])

@php
    $points = collect($points)->values();
    $hasData = $points->isNotEmpty() && $points->sum('value') > 0;

    $fmt = fn ($v) => ((float) $v == (int) $v) ? (string) (int) $v : number_format((float) $v, 1);

    $coords = [];
    $svg = null;

    if ($hasData) {
        $W = 640;
        $H = (int) $height;
        $padL = 42;
        $padR = 12;
        $padT = 14;
        $padB = 30;

        $values = $points->pluck('value')->map(fn ($v) => (float) $v)->all();
        $n = count($values);

        $maxVal = $yMax !== null ? (float) $yMax : max($values);
        if ($maxVal <= 0) {
            $maxVal = 1;
        }

        $innerW = $W - $padL - $padR;
        $innerH = $H - $padT - $padB;

        foreach ($values as $i => $v) {
            $x = $n === 1 ? $padL + ($innerW / 2) : $padL + ($i * $innerW / ($n - 1));
            $y = $padT + (1 - ($v / $maxVal)) * $innerH;
            $coords[] = [
                'x'     => round($x, 2),
                'y'     => round($y, 2),
                'v'     => $v,
                'label' => (string) ($points[$i]['label'] ?? ''),
            ];
        }

        $polyline = implode(' ', array_map(fn ($c) => $c['x'] . ',' . $c['y'], $coords));
        $first = $coords[0];
        $last = $coords[$n - 1];
        $baseline = $padT + $innerH;
        $areaPath = 'M ' . $first['x'] . ' ' . $baseline
            . ' L ' . implode(' L ', array_map(fn ($c) => $c['x'] . ' ' . $c['y'], $coords))
            . ' L ' . $last['x'] . ' ' . $baseline . ' Z';

        $ticks = [0, 0.25, 0.5, 0.75, 1];
        $step = max(1, (int) ceil($n / 8));
        $peak = max($values);

        $svg = [
            'W' => $W, 'H' => $H,
            'padL' => $padL, 'padR' => $padR, 'padT' => $padT, 'padB' => $padB,
            'innerH' => $innerH, 'maxVal' => $maxVal,
            'ticks' => $ticks, 'step' => $step, 'peak' => $peak,
        ];
    }
@endphp

@if (! $hasData)
    <x-charts.chart-empty-state :message="$emptyMessage" />
@else
    <figure class="line-chart-figure">
        <svg
            class="line-chart-svg"
            viewBox="0 0 {{ $svg['W'] }} {{ $svg['H'] }}"
            preserveAspectRatio="xMidYMid meet"
            role="img"
            aria-label="Line chart of {{ $valueLabel }} over {{ count($coords) }} periods. Peak value {{ $fmt($svg['peak']) }}."
        >
            @foreach ($svg['ticks'] as $t)
                @php $ty = $svg['padT'] + (1 - $t) * $svg['innerH']; @endphp
                <line class="grid-line" x1="{{ $svg['padL'] }}" y1="{{ $ty }}" x2="{{ $svg['W'] - $svg['padR'] }}" y2="{{ $ty }}"></line>
                <text class="axis-text" x="{{ $svg['padL'] - 7 }}" y="{{ $ty + 3.5 }}" text-anchor="end">{{ rtrim(rtrim(number_format($svg['maxVal'] * $t, 1), '0'), '.') }}</text>
            @endforeach

            <path class="series-area" d="{{ $areaPath ?? '' }}"></path>
            <polyline class="series-line" points="{{ $polyline ?? '' }}"></polyline>

            @foreach ($coords as $i => $c)
                <circle class="series-point" cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="3.5">
                    <title>{{ $c['label'] }}: {{ $fmt($c['v']) }}</title>
                </circle>
                @if ($i % $svg['step'] === 0 || $i === count($coords) - 1)
                    <text class="axis-text" x="{{ $c['x'] }}" y="{{ $svg['H'] - 8 }}" text-anchor="middle">{{ $c['label'] }}</text>
                @endif
            @endforeach
        </svg>

        <details class="chart-data">
            <summary>View data table</summary>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Period</th>
                        <th scope="col">{{ $valueLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coords as $c)
                        <tr>
                            <td>{{ $c['label'] }}</td>
                            <td>{{ $fmt($c['v']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </figure>
@endif
