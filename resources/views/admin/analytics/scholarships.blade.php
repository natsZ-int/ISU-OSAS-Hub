@extends('layouts.app')
@section('title', 'Scholarship Analytics — OSAS Hub')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Scholarship Analytics</h2>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0;">Application statistics, approval rates, and trends.</p>
        </div>
        <a href="{{ route('admin.analytics.export', $filters) }}" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Export CSV</a>
    </div>

    @include('admin.analytics._filters', [
        'filterAction' => route('admin.analytics.scholarships'),
        'showScholarshipFilter' => true,
    ])

    {{-- Tabs --}}
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;">
        <a href="{{ route('admin.analytics.index', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Overview</a>
        <a href="{{ route('admin.analytics.scholarships', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; color: #2563eb; border-bottom: 2px solid #2563eb; margin-bottom: -2px; text-decoration: none;">Scholarships</a>
        <a href="{{ route('admin.analytics.guidance', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Guidance</a>
        <a href="{{ route('admin.analytics.services', $filters) }}" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; color: #64748b; text-decoration: none;">Student Services</a>
    </div>

    {{-- Rate Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #16a34a;">{{ $data['approval_rate'] !== null ? $data['approval_rate'] . '%' : 'N/A' }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Approval Rate</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #dc2626;">{{ $data['rejection_rate'] !== null ? $data['rejection_rate'] . '%' : 'N/A' }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Rejection Rate</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #d97706;">{{ $data['pending'] }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Pending</div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #2563eb;">{{ $data['decided'] }}</div>
            <div style="font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Decided</div>
        </div>
    </div>

    {{-- Applications by Scholarship --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Applications by Scholarship</h4>
        @php $maxTotal = $data['byScholarship']->max('total') ?: 1; @endphp
        @forelse($data['byScholarship'] as $s)
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.6rem;">
                <span style="width: 200px; font-size: 0.8rem; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $s->title }}">{{ \Illuminate\Support\Str::limit($s->title, 28) }}</span>
                <div style="flex: 1; background: #f1f5f9; border-radius: 3px; height: 20px; display: flex; overflow: hidden;">
                    <div style="background: #16a34a; height: 100%; width: {{ ($s->approved / $maxTotal) * 100 }}%;"></div>
                    <div style="background: #dc2626; height: 100%; width: {{ ($s->rejected / $maxTotal) * 100 }}%;"></div>
                    <div style="background: #fbbf24; height: 100%; width: {{ ($s->pending / $maxTotal) * 100 }}%;"></div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; min-width: 25px; text-align: right;">{{ $s->total }}</span>
            </div>
        @empty
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No scholarship application data available.</p>
        @endforelse
        @if($data['byScholarship']->isNotEmpty())
            <div style="display: flex; gap: 1rem; margin-top: 0.75rem; font-size: 0.7rem; color: #64748b;">
                <span><span style="display: inline-block; width: 10px; height: 10px; background: #16a34a; border-radius: 2px;"></span> Approved</span>
                <span><span style="display: inline-block; width: 10px; height: 10px; background: #dc2626; border-radius: 2px;"></span> Rejected</span>
                <span><span style="display: inline-block; width: 10px; height: 10px; background: #fbbf24; border-radius: 2px;"></span> Pending</span>
            </div>
        @endif
    </div>

    {{-- Monthly Trend Table --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem;">
        <h4 style="font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem 0;">Application Volume by Month (Historical Trend)</h4>
        @if($data['byMonth']->isNotEmpty())
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th style="text-align: left; padding: 0.5rem; color: #64748b;">Month</th>
                        <th style="text-align: right; padding: 0.5rem; color: #64748b;">Applications</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['byMonth'] as $m)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.5rem;">{{ \Carbon\Carbon::parse($m->month . '-01')->format('F Y') }}</td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: 700;">{{ $m->total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1rem;">No monthly data available.</p>
        @endif
    </div>

</div>
@endsection