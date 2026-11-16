@extends('layouts.app')
@section('title', 'Review Scholarship Applications — Admin')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Scholarship Applications Review</h2>
            <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0;">Review submitted applications, verify documents, and transition workflow statuses.</p>
        </div>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('admin.scholarship-applications.index') }}" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div style="flex: 1; min-width: 220px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="App #, Student name, Student ID, Scholarship..." data-live-search data-live-search-endpoint="{{ route('admin.quick-search', ['module' => 'applications']) }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Status</label>
                <select name="status" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                    <option value="">All Statuses</option>
                    @foreach (\App\Models\ScholarshipApplication::ALLOWED_STATUSES as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" style="padding: 0.45rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Filter</button>
                <a href="{{ route('admin.scholarship-applications.index') }}" style="padding: 0.45rem 1rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; text-decoration: none; font-weight: 600;">Clear</a>
            </div>
        </div>
    </form>

    {{-- Applications Table --}}
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                        <th style="padding: 0.75rem 1rem;">Application #</th>
                        <th style="padding: 0.75rem 1rem;">Student</th>
                        <th style="padding: 0.75rem 1rem;">Scholarship</th>
                        <th style="padding: 0.75rem 1rem;">Submitted</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Status</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #1e293b;">
                                {{ $app->application_number }}
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600; color: #1e293b;">
                                    {{ $app->studentProfile?->first_name }} {{ $app->studentProfile?->last_name }}
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;">
                                    ID: {{ $app->studentProfile?->student_number ?? '—' }} | {{ $app->studentProfile?->program }}
                                </div>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #475569;">
                                {{ $app->scholarship?->title ?? '—' }}
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #64748b; white-space: nowrap;">
                                {{ $app->submitted_at ? $app->submitted_at->format('M d, Y h:i A') : 'Draft' }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                @php
                                    $rawStatus = is_object($app->status) ? $app->status->value : (string) $app->status;
                                @endphp
                                <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px;
                                    @if($rawStatus === 'approved') background: #dcfce7; color: #166534;
                                    @elseif($rawStatus === 'rejected') background: #fee2e2; color: #991b1b;
                                    @elseif($rawStatus === 'document_verification') background: #e0f2fe; color: #0369a1;
                                    @elseif($rawStatus === 'under_review') background: #fef3c7; color: #92400e;
                                    @elseif($rawStatus === 'submitted') background: #f1f5f9; color: #334155;
                                    @else background: #f1f5f9; color: #64748b; @endif">
                                    {{ ucfirst(str_replace('_', ' ', $rawStatus)) }}
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.scholarship-applications.show', $app) }}" style="padding: 0.35rem 0.75rem; background: #2563eb; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: 600;">Review &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 2.5rem; text-align: center; color: #64748b;">
                                No scholarship applications found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($applications, 'hasPages') && $applications->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection