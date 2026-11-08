@extends('layouts.app')
@section('title', 'My Service Requests - ISU OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">My Service Requests</h2>
            <p style="color: #6c757d; margin: 0;">Track the live status and history of your submitted requests.</p>
        </div>
        <a href="{{ route('student.services.catalog') }}" class="btn btn-primary" style="padding: 0.5rem 1rem;">New Service Request</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('status') }}</div>
    @endif

    <form method="GET" action="{{ route('student.services.index') }}" role="search"
          style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 1; min-width: 240px;">
                <label for="service_request_search" style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Search Requests</label>
                <input type="search" id="service_request_search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Request number, service type, status, or description..."
                       maxlength="200"
                       data-live-search
                       data-live-search-endpoint="{{ route('student.quick-search', ['module' => 'service-requests']) }}"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Search</button>
                @if (request('search'))
                    <a href="{{ route('student.services.index') }}" class="btn btn-outline" style="padding: 0.5rem 1rem; text-decoration: none;">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                    <th style="padding: 0.75rem 1rem;">Request #</th>
                    <th style="padding: 0.75rem 1rem;">Service Type</th>
                    <th style="padding: 0.75rem 1rem;">Date Submitted</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e3a8a;">{{ $req->request_number }}</td>
                        <td style="padding: 0.75rem 1rem;">{{ $req->serviceType->name }}</td>
                        <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $req->submitted_at ? $req->submitted_at->format('M d, Y') : 'N/A' }}</td>
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
                        <td style="padding: 0.75rem 1rem; text-align: right;">
                            <a href="{{ route('student.services.show', $req) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none;">Track Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 3rem; text-align: center; color: #64748b;">
                            @if (request('search'))
                                No service requests match "{{ request('search') }}".
                            @else
                                You have not submitted any service requests yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests instanceof \Illuminate\Pagination\LengthAwarePaginator && $requests->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection