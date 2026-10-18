@props([
    'rows' => [],
    'emptyMessage' => 'No data available for the selected filters.',
])

@php
    $rows = collect($rows)->values();
    $maxTotal = max([1, (int) $rows->max('total')]);

    $legend = $rows->flatMap(fn ($row) => collect($row['segments'] ?? [])->pluck('label'))
        ->unique()
        ->values();

    $fmt = fn ($v) => ((float) $v == (int) $v) ? (string) (int) $v : number_format((float) $v, 1);
@endphp

@if ($rows->isEmpty())
    <x-charts.chart-empty-state :message="$emptyMessage" />
@else
    <div class="stacked-chart">
        @foreach ($rows as $row)
            @php $barWidth = round(((int) $row['total'] / $maxTotal) * 100, 2); @endphp
            <div class="stacked-row">
                <div class="stacked-row-head">
                    <span class="stacked-name" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <span class="stacked-total">{{ $fmt($row['total']) }}</span>
                </div>
                <div class="hbar-track" style="height:16px;">
                    <div class="stacked-bar" style="width: {{ $barWidth }}%;">
                        @foreach (collect($row['segments'] ?? []) as $segment)
                            @if ((float) ($segment['value'] ?? 0) > 0)
                                <div
                                    class="stacked-seg tone-{{ $segment['tone'] ?? 'neutral' }}"
                                    style="width: {{ round(((float) $segment['value'] / max(1, (float) $row['total'])) * 100, 2) }}%;"
                                    title="{{ $segment['label'] }}: {{ $fmt($segment['value']) }}"
                                ></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($legend->isNotEmpty())
        <div class="chart-legend">
            @foreach ($legend as $i => $label)
                <span class="chart-legend-item">
                    <span class="chart-legend-swatch tone-{{ $rows[0]['segments'][$i]['tone'] ?? 'neutral' }}" aria-hidden="true"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    @endif

    <details class="chart-data">
        <summary>View data table</summary>
        <table>
            <thead>
                <tr>
                    <th scope="col">Category</th>
                    @foreach ($legend as $label)
                        <th scope="col">{{ $label }}</th>
                    @endforeach
                    <th scope="col">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        @foreach (collect($row['segments'] ?? []) as $segment)
                            <td>{{ $fmt($segment['value'] ?? 0) }}</td>
                        @endforeach
                        <td>{{ $fmt($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
@endif
