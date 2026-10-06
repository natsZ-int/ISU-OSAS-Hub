@extends('layouts.app')
@section('title', 'Requirements — ' . $scholarship->title)

@section('content')
<div class="page-header">
    <h2>Requirements: {{ $scholarship->title }}</h2>
    <a href="{{ route('admin.scholarships.edit', $scholarship) }}" class="btn btn-outline-dark">← Back to Scholarship</a>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())
    <div class="alert alert-error"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <h3>Add Requirement</h3>
    <form method="POST" action="{{ route('admin.scholarships.requirements.store', $scholarship) }}" class="filter-form">
        @csrf
        <div class="filter-row">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="requirement_name" required>
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" name="display_order" value="{{ $scholarship->requirements->count() + 1 }}" min="0">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_required" value="1" checked> Required</label>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description">
        </div>
    </form>
</div>

<div class="card table-card">
    <h3>Current Requirements</h3>
    @if ($scholarship->requirements->count() > 0)
    <table class="data-table">
        <thead><tr><th>#</th><th>Name</th><th>Required</th><th>Documents</th><th>Actions</th></tr></thead>
        <tbody>
            @foreach ($scholarship->requirements as $req)
            <tr>
                <td>{{ $req->display_order }}</td>
                <td>{{ $req->requirement_name }}</td>
                <td>{{ $req->is_required ? 'Yes' : 'No' }}</td>
                <td>{{ $req->documents->count() }}</td>
                <td class="actions-cell">
                    @if ($req->documents->count() === 0)
                        <form method="POST" action="{{ route('admin.scholarships.requirements.destroy', [$scholarship, $req]) }}" class="inline-form" onsubmit="return confirm('Delete this requirement?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    @else
                        <span class="muted">Has documents</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <p class="empty-state">No requirements yet.</p>
    @endif
</div>
@endsection