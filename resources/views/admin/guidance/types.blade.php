@extends('layouts.app')
@section('title', 'Appointment Types — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Guidance Appointment Types</h2>
    <a href="{{ route('admin.guidance.settings') }}" class="btn btn-outline-dark">Scheduling Settings</a>
</div>

{{-- Create New Type Form --}}
<div class="card" style="max-width: 600px; margin-bottom: 2rem;">
    <h3>Add Appointment Type</h3>
    <form method="POST" action="{{ route('admin.guidance.types.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Type Name</label>
            <input type="text" id="name" name="name" required maxlength="100" placeholder="e.g., Career Guidance">
        </div>
        <div class="form-group">
            <label for="description">Description (Optional)</label>
            <input type="text" id="description" name="description" maxlength="255">
        </div>
        <div class="form-group">
            <label for="duration_minutes">Duration (Minutes, optional override)</label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="5" max="480" placeholder="Leave blank for global default">
        </div>
        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" value="0" min="0">
        </div>
        <button type="submit" class="btn btn-primary">Create Type</button>
    </form>
</div>

{{-- Types Table --}}
@if ($types->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Name</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $t)
            <tr>
                <td>{{ $t->display_order }}</td>
                <td>
                    <strong>{{ $t->name }}</strong>
                    @if ($t->description)<br><small class="muted">{{ $t->description }}</small>@endif
                </td>
                <td>{{ $t->duration_minutes ? $t->duration_minutes . ' mins' : 'Default' }}</td>
                <td>
                    <span class="badge badge-{{ $t->is_active ? 'active' : 'inactive' }}">
                        {{ $t->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.guidance.types.toggle', $t) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline-dark btn-sm">
                            {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection