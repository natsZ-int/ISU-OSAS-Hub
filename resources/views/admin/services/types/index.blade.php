@extends('layouts.app')
@section('title', 'Service Categories - Admin OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">Service Types & Categories</h2>
            <p style="color: #6c757d; margin: 0;">Configure student services and document checklist requirements.</p>
        </div>
        <a href="{{ route('admin.service-types.create') }}" class="btn btn-primary" style="padding: 0.5rem 1rem;">Add Service Type</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('status') }}</div>
    @endif

    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                    <th style="padding: 0.75rem 1rem;">Order</th>
                    <th style="padding: 0.75rem 1rem;">Category Name</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem;">Total Requests</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($serviceTypes as $type)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $type->display_order }}</td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e3a8a;">{{ $type->name }}</td>
                        <td style="padding: 0.75rem 1rem;">
                            <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $type->is_active ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #64748b;' }}">
                                {{ $type->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem 1rem; color: #475569;">{{ $type->service_requests_count }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <a href="{{ route('admin.service-types.edit', $type) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none;">Edit</a>
                            <form action="{{ route('admin.service-types.toggle', $type) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; cursor: pointer;">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 3rem; text-align: center; color: #64748b;">No service types configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection