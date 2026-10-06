@extends('layouts.app')
@section('title', 'Reset Password — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Reset Password for {{ $user->name }}</h2>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">← Back to Users</a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card" style="max-width:500px;">
    <p class="muted">Set a new password for <strong>{{ $user->name }}</strong> ({{ $user->email }}).</p>
    <form method="POST" action="{{ route('admin.users.reset-password.update', $user) }}"
          onsubmit="return confirm('Are you sure you want to reset this user\'s password?')">
        @csrf @method('PATCH')
        <div class="form-group">
            <label for="password">New Password *</label>
            <input type="password" id="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm New Password *</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
</div>
@endsection