@extends('layouts.app')
@section('title', 'Analytics Dashboard — ISU OSAS Hub')

@section('content')
<div class="analytics-page">

    {{-- Report header --}}
    <div class="report-header">
        <div>
            <h1>Analytics Dashboard</h1>
            <p class="report-subtitle">
                Descriptive statistics for scholarships, guidance appointments, and student services.
            </p>
            <div class="report-meta">
                @if (! empty($filters['date_from']) || ! empty($filters['date_to']))
                    <span class="meta-chip">
                        Period: {{ $filters['date_from'] ?? 'Beginning' }} &mdash; {{ $filters['date_to'] ?? 'Present' }}
                    </span>
                @else
                    <span class="meta-chip">Period: All time</span>
                @endif
                <span>Generated {{ now()->format('M d, Y \a\t h:i A') }}</span>
            </div>
        </div>
        <div class="report-actions">
            <a href="{{ route('admin.analytics.export', $filters) }}" class="btn btn-primary btn-sm">Export CSV</a>
        </div>
    </div>

    {{-- Filters --}}
    @include('admin.analytics._filters', [
        'filterAction' => route('admin.analytics.index'),
        'showScholarshipFilter' => true,
        'showAppointmentFilter' => true,
        'showServiceFilter' => true,
    ])

    {{-- Section tabs --}}
    @include('admin.analytics._tabs')

    @php
        $hasAnyData = ($summary['total_applications'] + $summary['total_appointments'] + $summary['total_service_requests']) > 0;
    @endphp

    @if (! $hasAnyData)
        <x-card>
            <x-empty-state
                title="No analytics data available for the selected filters."
                message="Adjust the date range or clear the filters to see results."
                :actionUrl="route('admin.analytics.index')"
                actionLabel="Clear Filters"
            />
        </x-card>
    @else

        {{-- ── Scholarship applications ────────────────────────────── --}}
        <h3 class="section-heading">Scholarship Applications</h3>
        <div class="stat-grid">
            <x-metric-card label="Total Applications" :value="$summary['total_applications']" :href="route('admin.scholarship-applications.index')" hint="All recorded applications" />
            <x-metric-card label="Pending" :value="$summary['pending_applications']" tone="warning" hint="Awaiting review" />
            <x-metric-card label="Approved" :value="$summary['approved_applications']" tone="success" />
            <x-metric-card label="Rejected" :value="$summary['rejected_applications']" tone="danger" />
            <x-metric-card label="Approval Rate" :value="$summary['approval_rate'] !== null ? $summary['approval_rate'] . '%' : 'N/A'" tone="success" hint="Of decided applications" />
            <x-metric-card label="Rejection Rate" :value="$summary['rejection_rate'] !== null ? $summary['rejection_rate'] . '%' : 'N/A'" tone="danger" hint="Of decided applications" />
        </div>

        <div class="chart-grid">
            <x-charts.chart-card
                title="Application Volume by Month"
                subtitle="Number of scholarship applications submitted per month for the selected period."
            >
                <x-charts.line-chart
                    :points="$scholarships['byMonth']->map(fn ($m) => [
                        'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                        'value' => $m->total,
                    ])->values()->all()"
                    valueLabel="Applications"
                />
            </x-charts.chart-card>

            <x-charts.chart-card
                title="Scholarship Applications by Status"
                subtitle="Current distribution of scholarship applications by workflow status."
            >
                <x-charts.donut-chart
                    :slices="[
                        ['label' => 'Pending', 'value' => $summary['pending_applications'], 'tone' => 'warning'],
                        ['label' => 'Approved', 'value' => $summary['approved_applications'], 'tone' => 'success'],
                        ['label' => 'Rejected', 'value' => $summary['rejected_applications'], 'tone' => 'danger'],
                    ]"
                    centerLabel="Applications"
                    :centerValue="$summary['total_applications']"
                />
            </x-charts.chart-card>
        </div>

        {{-- ── Guidance appointments ───────────────────────────────── --}}
        <h3 class="section-heading">Guidance Appointments</h3>
        <div class="stat-grid">
            <x-metric-card label="Total Appointments" :value="$summary['total_appointments']" :href="route('admin.guidance.appointments')" />
            <x-metric-card label="Pending" :value="$summary['pending_appointments']" tone="warning" hint="Requested, not yet confirmed" />
            <x-metric-card label="Completed" :value="$summary['completed_appointments']" tone="success" />
            <x-metric-card label="Cancelled" :value="$summary['cancelled_appointments']" tone="neutral" />
        </div>

        <div class="chart-grid">
            <x-charts.chart-card
                title="Appointment Volume by Month"
                subtitle="Guidance appointments booked per month for the selected period."
            >
                <x-charts.line-chart
                    :points="$guidance['byMonth']->map(fn ($m) => [
                        'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                        'value' => $m->total,
                    ])->values()->all()"
                    valueLabel="Appointments"
                />
            </x-charts.chart-card>

            <x-charts.chart-card
                title="Appointments by Status"
                subtitle="Distribution of guidance appointments by workflow status."
            >
                <x-charts.donut-chart
                    :slices="[
                        ['label' => 'Pending', 'value' => $summary['pending_appointments'], 'tone' => 'warning'],
                        ['label' => 'Completed', 'value' => $summary['completed_appointments'], 'tone' => 'success'],
                        ['label' => 'Cancelled', 'value' => $summary['cancelled_appointments'], 'tone' => 'neutral'],
                    ]"
                    centerLabel="Appointments"
                    :centerValue="$summary['total_appointments']"
                />
            </x-charts.chart-card>
        </div>

        {{-- ── Student services ────────────────────────────────────── --}}
        <h3 class="section-heading">Student Services</h3>
        <div class="stat-grid">
            <x-metric-card label="Service Requests" :value="$summary['total_service_requests']" :href="route('admin.student-service-requests.index')" />
            <x-metric-card label="Pending" :value="$summary['pending_services']" tone="warning" hint="Awaiting action" />
            <x-metric-card label="Processing" :value="$summary['processing_services']" tone="info" />
            <x-metric-card label="Completed" :value="$summary['completed_services']" tone="success" />
        </div>

        <div class="chart-grid">
            <x-charts.chart-card
                title="Service Request Volume by Month"
                subtitle="Student service requests submitted per month for the selected period."
            >
                <x-charts.line-chart
                    :points="$services['byMonth']->map(fn ($m) => [
                        'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                        'value' => $m->total,
                    ])->values()->all()"
                    valueLabel="Service requests"
                />
            </x-charts.chart-card>

            <x-charts.chart-card
                title="Service Requests by Status"
                subtitle="Distribution of student service requests by workflow status."
            >
                <x-charts.donut-chart
                    :slices="[
                        ['label' => 'Pending', 'value' => $summary['pending_services'], 'tone' => 'warning'],
                        ['label' => 'Processing', 'value' => $summary['processing_services'], 'tone' => 'info'],
                        ['label' => 'Completed', 'value' => $summary['completed_services'], 'tone' => 'success'],
                    ]"
                    centerLabel="Requests"
                    :centerValue="$summary['total_service_requests']"
                />
            </x-charts.chart-card>
        </div>

    @endif

    <div class="report-note">
        <strong>Note:</strong> All metrics shown are descriptive historical statistics. This module does not provide
        predictions, forecasts, or AI-generated insights. Rates are calculated only from decided applications
        (approved + rejected); pending applications are excluded from rate denominators.
    </div>

    <div class="report-footer">
        <span>ISU OSAS Hub &mdash; Analytics &amp; Reports</span>
        <span>Generated {{ now()->format('M d, Y \a\t h:i A') }}</span>
    </div>

</div>
@endsection
