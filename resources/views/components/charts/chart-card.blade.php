@props(['title', 'subtitle' => null])

<figure class="chart-card" {{ $attributes }}>
    <figcaption class="chart-card-header">
        <div>
            <h3 class="chart-title">{{ $title }}</h3>
            @if ($subtitle)
                <p class="chart-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </figcaption>

    <div class="chart-body">
        {{ $slot }}
    </div>
</figure>
