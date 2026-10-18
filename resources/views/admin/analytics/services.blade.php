@extends('layouts.app')
@section('title', 'Student Services Analytics — ISU OSAS Hub')

@section('content')
<div class="analytics-page">

    <div class="report-header">
        <div>
            <h1>Student Services Analytics</h1>
            <p class="report-subtitle">
                Request volumes, distribution by service type, and average resolution processing time.
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

    @include('admin.analytics._filters', [
        'filterAction' => route('admin.analytics.services'),
        'showServiceFilter' => true,
    ])

    @include('admin.analytics._tabs')

    <div class="stat-grid">
        <x-metric-card
            label="Completed Requests"
            :value="$data['completed']"
            tone="success"
            :href="route('admin.student-service-requests.index')"
        />
        <x-metric-card label="Pending Queue" :value="$data['pending']" tone="warning" hint="Awaiting action" />
        <x-metric-card
            label="Avg Processing (Hours)"
            :value="$data['avg_processing_hours'] !== null ? $data['avg_processing_hours'] . ' hrs' : 'N/A'"
            tone="info"
        />
        <x-metric-card
            label="Avg Processing (Days)"
            :value="$data['avg_processing_days'] !== null ? $data['avg_processing_days'] . ' days' : 'N/A'"
            tone="info"
        />
    </div>

    <div class="chart-grid">
        <x-charts.chart-card
            title="Requests by Service Type"
            subtitle="Request volume per OSAS service type, split by workflow status."
        >
            <x-charts.stacked-bar-chart
                :rows="$data['byType']->map(fn ($t) => [
                    'label' => $t->type_name,
                    'total' => $t->total,
                    'segments' => [
                        ['label' => 'Submitted', 'value' => $t->submitted, 'tone' => 'warning'],
                        ['label' => 'Processing', 'value' => $t->processing, 'tone' => 'info'],
                        ['label' => 'Requires Info', 'value' => $t->requires_info, 'tone' => 'danger'],
                        ['label' => 'Completed', 'value' => $t->completed, 'tone' => 'success'],
                        ['label' => 'Cancelled', 'value' => $t->cancelled, 'tone' => 'neutral'],
                    ],
                ])->values()->all()"
            />
        </x-charts.chart-card>

        <x-charts.chart-card
            title="Request Volume by Month"
            subtitle="Historical monthly trend of student service requests for the selected period."
        >
            <x-charts.line-chart
                :points="$data['byMonth']->map(fn ($m) => [
                    'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                    'value' => $m->total,
                ])->values()->all()"
                valueLabel="Service requests"
            />
        </x-charts.chart-card>
    </div>

    <x-card title="Requests by Service Type — Detailed Breakdown">
        @if ($data['byType']->isNotEmpty())
            <x-table-wrapper label="Service requests by type">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Service Type</th>
                            <th scope="col" class="center">Submitted</th>
                            <th scope="col" class="center">Processing</th>
                            <th scope="col" class="center">Requires Info</th>
                            <th scope="col" class="center">Completed</th>
                            <th scope="col" class="center">Cancelled</th>
                            <th scope="col" class="center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['byType'] as $t)
                            <tr>
                                <td style="font-weight:600;">{{ $t->type_name }}</td>
                                <td class="center">{{ $t->submitted }}</td>
                                <td class="center">{{ $t->processing }}</td>
                                <td class="center">{{ $t->requires_info }}</td>
                                <td class="center">{{ $t->completed }}</td>
                                <td class="center">{{ $t->cancelled }}</td>
                                <td class="center" style="font-weight:700;">{{ $t->total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-wrapper>
        @else
            <x-empty-state
                title="No student service request data available."
                message="There are no records matching the selected filters."
            />
        @endif
    </x-card>

    <div class="report-footer">
        <span>ISU OSAS Hub &mdash; Student Services Analytics</span>
        <span>Generated {{ now()->format('M d, Y \a\t h:i A') }}</span>
    </div>

</div>
@endsection
