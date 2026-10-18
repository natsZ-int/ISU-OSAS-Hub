@extends('layouts.app')
@section('title', 'Login — ISU OSAS Hub')

@section('content')
<div class="card auth-card">
    <h1>Sign in to your account</h1>
    <p class="muted">Enter your credentials to continue.</p>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="button" class="password-toggle" data-toggle-password="#password"
                        aria-label="Show password" aria-pressed="false">Show</button>
            </div>
        </div>

        <div class="form-check">
            <label>
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>

    <p class="auth-alt-link muted">
        New student? <a href="{{ route('register') }}">Create a student account</a>
    </p>
</div>
@endsection
