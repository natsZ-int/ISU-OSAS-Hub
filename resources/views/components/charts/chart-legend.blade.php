@props(['items' => []])

@php $items = collect($items); @endphp

@if ($items->isNotEmpty())
    <div class="chart-legend">
        @foreach ($items as $item)
            <span class="chart-legend-item">
                <span
                    class="chart-legend-swatch tone-{{ $item['tone'] ?? 'brand' }}"
                    aria-hidden="true"
                ></span>
                {{ $item['label'] }}@isset($item['value']): <strong>{{ $item['value'] }}</strong>@endisset
            </span>
        @endforeach
    </div>
@endif
