@extends('layouts.app')
@section('title', 'Guidance Analytics — ISU OSAS Hub')

@section('content')
<div class="analytics-page">

    <div class="report-header">
        <div>
            <h1>Guidance Analytics</h1>
            <p class="report-subtitle">
                Appointment statistics, type breakdown, and counselor workload distribution.
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
        'filterAction' => route('admin.analytics.guidance'),
        'showAppointmentFilter' => true,
    ])

    @include('admin.analytics._tabs')

    <div class="stat-grid">
        <x-metric-card label="Completed" :value="$data['completed']" tone="success" hint="Sessions finished" />
        <x-metric-card label="Cancelled" :value="$data['cancelled']" tone="neutral" />
        <x-metric-card label="Pending (Requested)" :value="$data['pending']" tone="warning" hint="Awaiting counselor confirmation" />
    </div>

    <div class="chart-grid">
        <x-charts.chart-card
            title="Appointments by Type"
            subtitle="Appointment volume per counselling type, split by completed, cancelled, and pending outcomes."
        >
            <x-charts.stacked-bar-chart
                :rows="$data['byType']->map(fn ($t) => [
                    'label' => $t->type_name,
                    'total' => $t->total,
                    'segments' => [
                        ['label' => 'Completed', 'value' => $t->completed, 'tone' => 'success'],
                        ['label' => 'Cancelled', 'value' => $t->cancelled, 'tone' => 'neutral'],
                        ['label' => 'Pending', 'value' => $t->pending, 'tone' => 'warning'],
                    ],
                ])->values()->all()"
            />
        </x-charts.chart-card>

        <x-charts.chart-card
            title="Appointment Volume by Month"
            subtitle="Historical monthly trend of guidance appointments for the selected period."
        >
            <x-charts.line-chart
                :points="$data['byMonth']->map(fn ($m) => [
                    'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                    'value' => $m->total,
                ])->values()->all()"
                valueLabel="Appointments"
            />
        </x-charts.chart-card>
    </div>

    <x-card title="Counselor Workload Distribution">
        <p class="muted">
            Aggregate appointment counts per counselor. This is workload distribution, not a performance evaluation.
        </p>

        @if ($data['byCounselor']->isNotEmpty())
            <x-table-wrapper label="Counselor workload distribution">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Counselor</th>
                            <th scope="col" class="center">Pending</th>
                            <th scope="col" class="center">Confirmed</th>
                            <th scope="col" class="center">Completed</th>
                            <th scope="col" class="center">Cancelled</th>
                            <th scope="col" class="center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['byCounselor'] as $c)
                            <tr>
                                <td style="font-weight:600;">{{ $c->counselor_name }}</td>
                                <td class="center">{{ $c->pending }}</td>
                                <td class="center">{{ $c->confirmed }}</td>
                                <td class="center">{{ $c->completed }}</td>
                                <td class="center">{{ $c->cancelled }}</td>
                                <td class="center" style="font-weight:700;">{{ $c->total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-wrapper>
        @else
            <x-empty-state
                title="No counselor assignment data available."
                message="There are no appointments matching the selected filters."
            />
        @endif
    </x-card>

    <div class="report-footer">
        <span>ISU OSAS Hub &mdash; Guidance Analytics</span>
        <span>Generated {{ now()->format('M d, Y \a\t h:i A') }}</span>
    </div>

</div>
@endsection
