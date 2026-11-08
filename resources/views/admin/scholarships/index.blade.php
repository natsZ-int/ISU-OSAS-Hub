@extends('layouts.app')
@section('title', 'Manage Scholarships - Admin')

@section('content')
<x-page-header
    title="Scholarship Management"
    subtitle="Create, edit, activate, deactivate, and manage requirements for scholarships.">
    <x-slot:actions>
        <a href="{{ route('admin.scholarships.create') }}" class="btn btn-primary">+ Create Scholarship</a>
    </x-slot:actions>
</x-page-header>

<x-card class="filter-card">
    <form method="GET" action="{{ route('admin.scholarships.index') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group" style="flex: 2;">
                <label for="search">Search Title or Provider</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Search scholarships...">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="open" @selected(request('status') === 'open')>Open</option>
                    <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.scholarships.index') }}" class="btn btn-outline-dark">Reset</a>
            </div>
        </div>
    </form>
</x-card>

<x-card class="table-card">
    @if ($scholarships->count() > 0)
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Scholarship Title</th>
                        <th>Provider</th>
                        <th>Type</th>
                        <th style="text-align:center;">Applications</th>
                        <th>Deadline</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scholarships as $s)
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--text);">{{ $s->title }}</div>
                                @if ($s->minimum_gwa)
                                    <div class="text-muted" style="font-size:.75rem;">Min GWA: {{ $s->minimum_gwa }}</div>
                                @endif
                            </td>
                            <td>{{ $s->provider }}</td>
                            <td style="text-transform:capitalize;">{{ $s->scholarship_type }}</td>
                            <td style="text-align:center;">
                                <span class="count-badge">{{ $s->applications_count ?? $s->applications()->count() }}</span>
                            </td>
                            <td style="white-space:nowrap;">
                                {{ $s->application_deadline ? $s->application_deadline->format('M d, Y') : '—' }}
                            </td>
                            <td style="text-align:center;">
                                <x-status-badge :status="$s->status" />
                            </td>
                            <td class="actions-cell" style="justify-content:flex-end;">
                                <a href="{{ route('admin.scholarships.edit', $s) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                                <a href="{{ route('admin.scholarships.requirements.index', $s) }}" class="btn btn-sm btn-outline-dark">Requirements</a>

                                @if ($s->status !== 'open')
                                    <x-confirm-button
                                        :action="route('admin.scholarships.activate', $s)"
                                        method="PATCH"
                                        :confirm="'Activate the scholarship \'' . $s->title . '\'?'"
                                        label="Activate"
                                        variant="success"
                                        size="sm" />
                                @else
                                    <x-confirm-button
                                        :action="route('admin.scholarships.announce', $s)"
                                        method="PATCH"
                                        :confirm="'Email all eligible students about \'' . $s->title . '\'?'"
                                        label="Notify Eligible"
                                        variant="outline-dark"
                                        size="sm" />
                                    <x-confirm-button
                                        :action="route('admin.scholarships.deactivate', $s)"
                                        method="PATCH"
                                        :confirm="'Deactivate the scholarship \'' . $s->title . '\'? Students will no longer be able to apply.'"
                                        label="Deactivate"
                                        variant="danger"
                                        size="sm" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-pagination-wrapper :paginator="$scholarships" />
    @else
        <x-empty-state
            title="No scholarships found."
            message="Create your first scholarship to start accepting applications."
            :action-url="route('admin.scholarships.create')"
            action-label="+ Create Scholarship" />
    @endif
</x-card>
@endsection