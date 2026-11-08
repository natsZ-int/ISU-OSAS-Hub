@php
    $searches = $searches ?? [];
    $recordSearchTitle = $recordSearchTitle ?? 'Find a Record';
    $recordSearchHint = $recordSearchHint
        ?? 'Type to see matching records. Choosing a suggestion opens that record; the analytics totals above are unaffected.';
@endphp

@if (! empty($searches))
    <x-card class="filter-card record-search-card" :title="$recordSearchTitle">
        <p class="muted">{{ $recordSearchHint }}</p>

        <div class="filter-row" style="align-items:flex-end; flex-wrap:wrap;">
            @foreach ($searches as $search)
                <div class="form-group" style="flex:1 1 260px;">
                    <label for="{{ $search['id'] }}">{{ $search['field'] }}</label>
                    <x-live-search
                        :id="$search['id']"
                        name="record_search"
                        :endpoint="$search['endpoint']"
                        :placeholder="$search['placeholder'] ?? 'Search...'"
                        :label="$search['results'] ?? 'records'" />
                </div>
            @endforeach
        </div>
    </x-card>
@endif
