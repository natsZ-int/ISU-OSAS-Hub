@extends('layouts.app')
@section('title', 'Audit Log Detail - Admin OSAS Hub')

@section('content')
<div class="container py-4" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.audit-logs.index') }}" style="color: #4b5563; text-decoration: none; font-size: 0.9rem;">&larr; Back to Audit Logs</a>
    </div>

    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem;">
        <h2 style="font-size: 1.3rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.5rem;">Audit Log Detail</h2>
        <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 1.5rem;">This record is append-only and cannot be modified.</p>

        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; width: 30%; font-weight: 600;">Timestamp</th>
                <td style="padding: 0.75rem 1rem; color: #1e293b;">{{ $auditLog->created_at ? $auditLog->created_at->format('F d, Y h:i:s A') : 'N/A' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Acting User</th>
                <td style="padding: 0.75rem 1rem; color: #1e293b;">
                    @if($auditLog->user)
                        {{ $auditLog->user->name }} ({{ $auditLog->user->email }})
                    @else
                        System / Anonymous
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Action</th>
                <td style="padding: 0.75rem 1rem;">
                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                        {{ $auditLog->action }}
                    </span>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Module</th>
                <td style="padding: 0.75rem 1rem; color: #1e293b;">{{ $auditLog->module }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Record Type</th>
                <td style="padding: 0.75rem 1rem; color: #1e293b;">{{ $auditLog->record_type ?? '—' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Record ID</th>
                <td style="padding: 0.75rem 1rem; color: #1e293b;">{{ $auditLog->record_id ?? '—' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">Description</th>
                <td style="padding: 0.75rem 1rem; color: #334155; line-height: 1.5;">{{ $auditLog->description ?? '—' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">IP Address</th>
                <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $auditLog->ip_address ?? '—' }}</td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 1rem; text-align: left; color: #64748b; font-weight: 600;">User Agent</th>
                <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.8rem; word-break: break-all;">{{ $auditLog->user_agent ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection