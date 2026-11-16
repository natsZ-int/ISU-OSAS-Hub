@extends('layouts.app')
@section('title', 'Service Requests - Admin OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">Student Service Requests</h2>
        <p style="color: #6c757d; margin: 0;">Review, assign, process, and complete incoming service requests from students.</p>
    </div>

    <!-- Filter Bar -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form action="{{ route('admin.student-service-requests.index') }}" method="GET" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr auto; gap: 0.75rem; align-items: center;">
            <input type="text" name="search" placeholder="Search request #, student name..." value="{{ request('search') }}" data-live-search data-live-search-endpoint="{{ route('admin.quick-search', ['module' => 'service-requests']) }}" style="border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
            
            <select name="service_type_id" style="border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
                <option value="">All Service Types</option>
                @foreach($serviceTypes as $st)
                    <option value="{{ $st->id }}" {{ request('service_type_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>

            <select name="status" style="border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
                <option value="">All Statuses</option>
                @foreach(['submitted', 'processing', 'requires_information', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>

            <select name="handler_id" style="border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
                <option value="">All Handlers</option>
                @foreach($handlers as $h)
                    <option value="{{ $h->id }}" {{ request('handler_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Filter</button>
        </form>
    </div>

    <!-- Requests Table -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                    <th style="padding: 0.75rem 1rem;">Request #</th>
                    <th style="padding: 0.75rem 1rem;">Student</th>
                    <th style="padding: 0.75rem 1rem;">Service Type</th>
                    <th style="padding: 0.75rem 1rem;">Handler</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem;">Submitted</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e3a8a;">{{ $req->request_number }}</td>
                        <td style="padding: 0.75rem 1rem;">
                            {{ $req->studentProfile->first_name }} {{ $req->studentProfile->last_name }}
                            <small style="display: block; color: #64748b;">{{ $req->studentProfile->student_number }}</small>
                        </td>
                        <td style="padding: 0.75rem 1rem;">{{ $req->serviceType->name }}</td>
                        <td style="padding: 0.75rem 1rem; color: #475569;">{{ $req->handler?->name ?? 'Unassigned' }}</td>
                        <td style="padding: 0.75rem 1rem;">
                            @php
                                $badgeStyles = match($req->status) {
                                    'submitted' => 'background: #e0f2fe; color: #0369a1;',
                                    'processing' => 'background: #fef3c7; color: #92400e;',
                                    'requires_information' => 'background: #fee2e2; color: #991b1b;',
                                    'completed' => 'background: #dcfce7; color: #166534;',
                                    'cancelled' => 'background: #f3f4f6; color: #4b5563;',
                                    default => 'background: #f3f4f6; color: #4b5563;',
                                };
                            @endphp
                            <span style="display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $badgeStyles }}">
                                {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $req->submitted_at ? $req->submitted_at->format('M d, Y') : 'N/A' }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: right;">
                            <a href="{{ route('admin.student-service-requests.show', $req) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none;">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 3rem; text-align: center; color: #64748b;">No service requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection