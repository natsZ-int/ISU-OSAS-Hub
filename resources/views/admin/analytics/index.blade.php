@extends('layouts.app')
@section('title', 'Analytics Dashboard — OSAS Hub')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">

    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Analytics Dashboard</h2>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0;">Descriptive statistics for scholarships, guidance, and student services.</p>
        </div>
        <a href="{{ route('admin.analytics.export', $filters) }}" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Export CSV</a>
    </div>

    {{-- Period Label --}}
    @if(!empty($filters['date_from']) || !empty($filters['date_to']))
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.5rem 1rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1e40af;">
            Analytics Period: {{ $filters['date_from'] ?? 'Beginning' }} — {{ $filters['date_to'] ?? 'Present' }}
        </div>
    @endif

    {{-- Filters --}}
    @include('admin.analytics._filters', [
        'filterAction' => route('admin.analytics.index'),
        'showScholarshipFilter' => true,
        'showAppointmentFilter' => true,
        'showServiceFilter' => true,
    ])

    {{-- Navigation Tabs --}}
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0;">
        <a href="{{ route('admin.analytics.index', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; color: #2563eb; border-bottom: 2px solid #2563eb; margin-bottom: -2px; text-decoration: none;">Overview</a>
        <a href="{{ route('admin.analytics.scholarships', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Scholarships</a>
        <a href="{{ route('admin.analytics.guidance', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Guidance</a>
        <a href="{{ route('admin.analytics.services', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Student Services</a>
    </div>

    {{-- Summary Cards --}}
    <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">Summary Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        @php
            $cards = [
                ['label' => 'Total Applications', 'value' => $summary['total_applications'], 'color' => '#2563eb'],
                ['label' => 'Pending Apps', 'value' => $summary['pending_applications'], 'color' => '#d97706'],
                ['label' => 'Approved', 'value' => $summary['approved_applications'], 'color' => '#16a34a'],
                ['label' => 'Rejected', 'value' => $summary['rejected_applications'], 'color' => '#dc2626'],
                ['label' => 'Approval Rate', 'value' => $summary['approval_rate'] !== null ? $summary['approval_rate'] . '%' : 'N/A', 'color' => '#16a34a'],
                ['label' => 'Rejection Rate', 'value' => $summary['rejection_rate'] !== null ? $summary['rejection_rate'] . '%' : 'N/A', 'color' => '#dc2626'],
                ['label' => 'Total Appointments', 'value' => $summary['total_appointments'], 'color' => '#7c3aed'],
                ['label' => 'Pending Appts', 'value' => $summary['pending_appointments'], 'color' => '#d97706'],
                ['label' => 'Completed Appts', 'value' => $summary['completed_appointments'], 'color' => '#16a34a'],
                ['label' => 'Cancelled Appts', 'value' => $summary['cancelled_appointments'], 'color' => '#dc2626'],
                ['label' => 'Service Requests', 'value' => $summary['total_service_requests'], 'color' => '#0891b2'],
                ['label' => 'Pending Services', 'value' => $summary['pending_services'], 'color' => '#d97706'],
                ['label' => 'Processing', 'value' => $summary['processing_services'], 'color' => '#2563eb'],
                ['label' => 'Completed Services', 'value' => $summary['completed_services'], 'color' => '#16a34a'],
            ];
        @endphp
        @foreach($cards as $card)
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 800; color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; margin-top: 0.25rem;">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">

        {{-- Scholarship Applications by Month --}}
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
            <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Application Volume by Month</h4>
            @php $maxAppMonth = $scholarships['byMonth']->max('total') ?: 1; @endphp
            @forelse($scholarships['byMonth'] as $m)
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                    <span style="width: 65px; font-size: 0.75rem; color: #64748b;">{{ \Carbon\Carbon::parse($m->month . '-01')->format('M Y') }}</span>
                    <div style="flex: 1; background: #f1f5f9; border-radius: 3px; height: 18px;">
                        <div style="background: #3b82f6; height: 100%; width: {{ ($m->total / $maxAppMonth) * 100 }}%; border-radius: 3px; min-width: 2px;"></div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; min-width: 25px; text-align: right;">{{ $m->total }}</span>
                </div>
            @empty
                <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No application data available for the selected period.</p>
            @endforelse
        </div>

        {{-- Appointments by Month --}}
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
            <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Appointment Volume by Month</h4>
            @php $maxApptMonth = $guidance['byMonth']->max('total') ?: 1; @endphp
            @forelse($guidance['byMonth'] as $m)
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                    <span style="width: 65px; font-size: 0.75rem; color: #64748b;">{{ \Carbon\Carbon::parse($m->month . '-01')->format('M Y') }}</span>
                    <div style="flex: 1; background: #f1f5f9; border-radius: 3px; height: 18px;">
                        <div style="background: #7c3aed; height: 100%; width: {{ ($m->total / $maxApptMonth) * 100 }}%; border-radius: 3px; min-width: 2px;"></div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; min-width: 25px; text-align: right;">{{ $m->total }}</span>
                </div>
            @empty
                <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No appointment data available for the selected period.</p>
            @endforelse
        </div>
    </div>

    {{-- Service Requests by Month --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 2rem;">
        <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Service Request Volume by Month</h4>
        @php $maxSvcMonth = $services['byMonth']->max('total') ?: 1; @endphp
        @forelse($services['byMonth'] as $m)
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                <span style="width: 65px; font-size: 0.75rem; color: #64748b;">{{ \Carbon\Carbon::parse($m->month . '-01')->format('M Y') }}</span>
                <div style="flex: 1; background: #f1f5f9; border-radius: 3px; height: 18px;">
                    <div style="background: #0891b2; height: 100%; width: {{ ($m->total / $maxSvcMonth) * 100 }}%; border-radius: 3px; min-width: 2px;"></div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; min-width: 25px; text-align: right;">{{ $m->total }}</span>
            </div>
        @empty
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No service request data available for the selected period.</p>
        @endforelse
    </div>

    {{-- Disclaimer --}}
    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 0.75rem 1rem; font-size: 0.8rem; color: #92400e;">
        <strong>Note:</strong> All metrics shown are descriptive historical statistics. This module does not provide predictions, forecasts, or AI-generated insights. Rates are calculated only from decided applications (approved + rejected); pending applications are excluded from rate denominators.
    </div>

</div>
@endsection