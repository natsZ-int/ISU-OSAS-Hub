@php
    $filters = $filters ?? [];
    $filterOptions = $filterOptions ?? [
        'scholarships'      => collect(),
        'serviceTypes'      => collect(),
        'appointmentTypes'  => collect(),
    ];

    $quickFindModule = match (true) {
        request()->routeIs('admin.analytics.scholarships') => 'analytics-scholarships',
        request()->routeIs('admin.analytics.guidance') => 'analytics-guidance',
        request()->routeIs('admin.analytics.services') => 'analytics-services',
        default => 'analytics',
    };
@endphp

<div class="card filter-card">
    <x-live-search
        :endpoint="route('admin.quick-search', ['module' => $quickFindModule])"
        name="quick_find"
        id="analytics-quick-find"
        label="Quick Find"
        placeholder="Jump straight to an application, appointment, or service request..."
    />
</div>

<x-filter-bar :action="$filterAction ?? route('admin.analytics.index')">
    <x-filter-field label="Date From" for-id="filter_date_from">
        <input
            type="date"
            id="filter_date_from"
            name="date_from"
            value="{{ $filters['date_from'] ?? '' }}"
            max="{{ date('Y-m-d') }}"
        >
    </x-filter-field>

    <x-filter-field label="Date To" for-id="filter_date_to">
        <input
            type="date"
            id="filter_date_to"
            name="date_to"
            value="{{ $filters['date_to'] ?? '' }}"
            max="{{ date('Y-m-d') }}"
        >
    </x-filter-field>

    @if (($showScholarshipFilter ?? false) && ! empty($filterOptions['scholarships']) && $filterOptions['scholarships']->isNotEmpty())
        <x-filter-field label="Scholarship" for-id="filter_scholarship_id">
            <select id="filter_scholarship_id" name="scholarship_id">
                <option value="">All scholarships</option>
                @foreach ($filterOptions['scholarships'] as $id => $title)
                    <option value="{{ $id }}" {{ ($filters['scholarship_id'] ?? '') == $id ? 'selected' : '' }}>
                        {{ $title }}
                    </option>
                @endforeach
            </select>
        </x-filter-field>
    @endif

    @if (($showAppointmentFilter ?? false) && ! empty($filterOptions['appointmentTypes']) && $filterOptions['appointmentTypes']->isNotEmpty())
        <x-filter-field label="Appointment Type" for-id="filter_appointment_type_id">
            <select id="filter_appointment_type_id" name="appointment_type_id">
                <option value="">All appointment types</option>
                @foreach ($filterOptions['appointmentTypes'] as $id => $name)
                    <option value="{{ $id }}" {{ ($filters['appointment_type_id'] ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </x-filter-field>
    @endif

    @if (($showServiceFilter ?? false) && ! empty($filterOptions['serviceTypes']) && $filterOptions['serviceTypes']->isNotEmpty())
        <x-filter-field label="Service Type" for-id="filter_service_type_id">
            <select id="filter_service_type_id" name="service_type_id">
                <option value="">All service types</option>
                @foreach ($filterOptions['serviceTypes'] as $id => $name)
                    <option value="{{ $id }}" {{ ($filters['service_type_id'] ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </x-filter-field>
    @endif

    <x-slot:actions>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-loading-label="Applying filters…">
                Apply Filters
            </button>
            <a href="{{ $filterAction ?? route('admin.analytics.index') }}" class="btn btn-outline-dark btn-sm">
                Clear Filters
            </a>
        </div>

        @if (! empty($filters))
            <div class="filter-chips">
                <span class="filter-chip-label">Active filters:</span>
                @foreach ($filters as $key => $value)
                    <span class="filter-chip">{{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </x-slot:actions>
</x-filter-bar>
