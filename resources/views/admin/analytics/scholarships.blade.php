@extends('layouts.app')
@section('title', 'Scholarship Analytics — ISU OSAS Hub')

@section('content')
<div class="analytics-page">

    <div class="report-header">
        <div>
            <h1>Scholarship Analytics</h1>
            <p class="report-subtitle">
                Application statistics, approval rates, and monthly trends for scholarship grants.
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
        'filterAction' => route('admin.analytics.scholarships'),
        'showScholarshipFilter' => true,
    ])

    @include('admin.analytics._tabs')

    <div class="stat-grid">
        <x-metric-card
            label="Approval Rate"
            :value="$data['approval_rate'] !== null ? $data['approval_rate'] . '%' : 'N/A'"
            tone="success"
            hint="Of decided applications"
        />
        <x-metric-card
            label="Rejection Rate"
            :value="$data['rejection_rate'] !== null ? $data['rejection_rate'] . '%' : 'N/A'"
            tone="danger"
            hint="Of decided applications"
        />
        <x-metric-card label="Pending" :value="$data['pending']" tone="warning" />
        <x-metric-card label="Total Decided" :value="$data['decided']" tone="info" hint="Approved + rejected" />
    </div>

    <div class="chart-grid">
        <x-charts.chart-card
            title="Applications by Scholarship"
            subtitle="Top scholarships by application volume, broken down by approved, rejected, and pending outcomes."
        >
            <x-charts.stacked-bar-chart
                :rows="$data['byScholarship']->map(fn ($s) => [
                    'label' => \Illuminate\Support\Str::limit($s->title, 42),
                    'total' => $s->total,
                    'segments' => [
                        ['label' => 'Approved', 'value' => $s->approved, 'tone' => 'success'],
                        ['label' => 'Rejected', 'value' => $s->rejected, 'tone' => 'danger'],
                        ['label' => 'Pending', 'value' => $s->pending, 'tone' => 'warning'],
                    ],
                ])->values()->all()"
            />
        </x-charts.chart-card>

        <x-charts.chart-card
            title="Application Volume by Month"
            subtitle="Historical monthly trend of scholarship applications for the selected period."
        >
            <x-charts.line-chart
                :points="$data['byMonth']->map(fn ($m) => [
                    'label' => \Carbon\Carbon::parse($m->month . '-01')->format('M y'),
                    'value' => $m->total,
                ])->values()->all()"
                valueLabel="Applications"
            />
        </x-charts.chart-card>
    </div>

    <div class="report-footer">
        <span>ISU OSAS Hub &mdash; Scholarship Analytics</span>
        <span>Generated {{ now()->format('M d, Y \a\t h:i A') }}</span>
    </div>

</div>
@endsection
