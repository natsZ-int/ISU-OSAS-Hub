@extends('layouts.app')
@section('title', 'Audit Logs - Admin OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">System Audit Logs</h2>
        <p style="color: #6c757d; margin: 0;">
            {{ $totalCount }} audit record(s) found. Logs are append-only and cannot be edited or deleted.
        </p>
    </div>

    <!-- Filter Bar -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form action="{{ route('admin.audit-logs.index') }}" method="GET">
            <div style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Description, action, user..." data-live-search data-live-search-endpoint="{{ route('admin.quick-search', ['module' => 'audit-logs']) }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">User</label>
                    <select name="user_id" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">Module</label>
                    <select name="module" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                        <option value="">All Modules</option>
                        @foreach($modules as $m)
                            <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">Action</label>
                    <select name="action" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                        <option value="">All Actions</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1.5fr 1.5fr auto auto; gap: 0.75rem; align-items: end;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">Record Type</label>
                    <select name="record_type" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                        <option value="">All Types</option>
                        @foreach($recordTypes as $rt)
                            <option value="{{ $rt }}" {{ request('record_type') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem;">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.85rem;">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline" style="padding: 0.45rem 1rem; font-size: 0.85rem; text-decoration: none;">Clear</a>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem; min-width: 900px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600;">
                    <th style="padding: 0.65rem 0.75rem;">Date / Time</th>
                    <th style="padding: 0.65rem 0.75rem;">User</th>
                    <th style="padding: 0.65rem 0.75rem;">Action</th>
                    <th style="padding: 0.65rem 0.75rem;">Module</th>
                    <th style="padding: 0.65rem 0.75rem;">Record</th>
                    <th style="padding: 0.65rem 0.75rem;">Description</th>
                    <th style="padding: 0.65rem 0.75rem;">IP</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: right;">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.6rem 0.75rem; white-space: nowrap; color: #475569;">
                            {{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A' }}
                        </td>
                        <td style="padding: 0.6rem 0.75rem;">
                            {{ $log->user?->name ?? 'System' }}
                        </td>
                        <td style="padding: 0.6rem 0.75rem;">
                            <span style="display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="padding: 0.6rem 0.75rem; color: #475569;">{{ $log->module }}</td>
                        <td style="padding: 0.6rem 0.75rem; color: #64748b; font-size: 0.8rem;">
                            @if($log->record_type)
                                {{ $log->record_type }} #{{ $log->record_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding: 0.6rem 0.75rem; color: #334155; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $log->description }}
                        </td>
                        <td style="padding: 0.6rem 0.75rem; color: #94a3b8; font-size: 0.8rem;">{{ $log->ip_address ?? '—' }}</td>
                        <td style="padding: 0.6rem 0.75rem; text-align: right;">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" style="font-size: 0.8rem; color: #2563eb; text-decoration: none;">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 3rem; text-align: center; color: #64748b;">
                            No audit records match the current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection