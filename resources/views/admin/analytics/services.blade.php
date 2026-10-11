@extends('layouts.app')
@section('title', 'Student Services Analytics — OSAS Hub')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Student Services Analytics</h2>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0;">Request volumes, distribution by type, and average resolution processing time.</p>
        </div>
        <a href="{{ route('admin.analytics.export', $filters) }}" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Export CSV</a>
    </div>

    @include('admin.analytics._filters', [
        'filterAction' => route('admin.analytics.services'),
        'showServiceFilter' => true,
    ])

    {{-- Tabs --}}
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;">
        <a href="{{ route('admin.analytics.index', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Overview</a>
        <a href="{{ route('admin.analytics.scholarships', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Scholarships</a>
        <a href="{{ route('admin.analytics.guidance', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Guidance</a>
        <a href="{{ route('admin.analytics.services', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; color: #0891b2; border-bottom: 2px solid #0891b2; margin-bottom: -2px; text-decoration: none;">Student Services</a>
    </div>

    {{-- Metric Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #16a34a;">{{ $data['completed'] }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Completed Requests</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #d97706;">{{ $data['pending'] }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Pending Queue</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #0891b2;">
                {{ $data['avg_processing_hours'] !== null ? $data['avg_processing_hours'] . ' hrs' : 'N/A' }}
            </div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Avg Processing (Hours)</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #2563eb;">
                {{ $data['avg_processing_days'] !== null ? $data['avg_processing_days'] . ' days' : 'N/A' }}
            </div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Avg Processing (Days)</div>
        </div>
    </div>

    {{-- Requests by Service Type --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Requests by Service Type</h4>
        @if($data['byType']->isNotEmpty())
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th style="text-align: left; padding: 0.5rem; color: #64748b;">Service Type</th>
                        <th style="text-align: center; padding: 0.5rem; color: #d97706;">Submitted</th>
                        <th style="text-align: center; padding: 0.5rem; color: #2563eb;">Processing</th>
                        <th style="text-align: center; padding: 0.5rem; color: #991b1b;">Requires Info</th>
                        <th style="text-align: center; padding: 0.5rem; color: #16a34a;">Completed</th>
                        <th style="text-align: center; padding: 0.5rem; color: #dc2626;">Cancelled</th>
                        <th style="text-align: center; padding: 0.5rem; color: #1e293b;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['byType'] as $t)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.5rem; font-weight: 600;">{{ $t->type_name }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $t->submitted }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $t->processing }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $t->requires_info }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $t->completed }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $t->cancelled }}</td>
                            <td style="padding: 0.5rem; text-align: center; font-weight: 700;">{{ $t->total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No student service request data available.</p>
        @endif
    </div>

    {{-- Monthly Volume --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
        <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Request Volume by Month (Historical)</h4>
        @php $maxSvc = $data['byMonth']->max('total') ?: 1; @endphp
        @forelse($data['byMonth'] as $m)
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                <span style="width: 65px; font-size: 0.75rem; color: #64748b;">{{ \Carbon\Carbon::parse($m->month . '-01')->format('M Y') }}</span>
                <div style="flex: 1; background: #f1f5f9; border-radius: 3px; height: 18px;">
                    <div style="background: #0891b2; height: 100%; width: {{ ($m->total / $maxSvc) * 100 }}%; border-radius: 3px; min-width: 2px;"></div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; min-width: 25px; text-align: right;">{{ $m->total }}</span>
            </div>
        @empty
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No monthly data available.</p>
        @endforelse
    </div>

</div>
@endsection