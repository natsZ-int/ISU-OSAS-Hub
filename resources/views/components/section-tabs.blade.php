@props(['tabs' => []])

<nav class="section-tabs" aria-label="Section navigation">
    @foreach ($tabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            class="section-tab{{ !empty($tab['active']) ? ' is-active' : '' }}"
            @if (!empty($tab['active'])) aria-current="page" @endif
        >{{ $tab['label'] }}</a>
    @endforeach
</nav>
