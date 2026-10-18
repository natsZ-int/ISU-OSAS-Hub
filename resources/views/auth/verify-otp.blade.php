@extends('layouts.app')
@section('title', 'Verify Email — ISU OSAS Hub')

@section('content')
<div class="card auth-card">
    <h1>Verify Your Email</h1>
    <p class="muted">
        We sent a six-digit verification code to <strong>{{ auth()->user()->email }}</strong>.
        Enter it below to verify your email address. The code expires in
        {{ \App\Services\OtpVerificationService::EXPIRY_MINUTES }} minutes.
    </p>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="code">Verification Code <span class="field-required">*</span></label>
            <input type="text" id="code" name="code" class="otp-input"
                   value="{{ old('code') }}"
                   inputmode="numeric" pattern="[0-9]*" maxlength="6"
                   autocomplete="one-time-code" required autofocus
                   aria-describedby="code-hint">
            <small class="field-hint" id="code-hint">Enter the 6-digit code from your email.</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Verify Email</button>
    </form>

    <form method="POST" action="{{ route('verification.resend') }}" class="resend-form">
        @csrf
        <button type="submit" id="resend-button" class="btn btn-outline-dark btn-block"
                @if ($cooldown > 0) disabled @endif>
            Resend Code
        </button>
        @if ($cooldown > 0)
            <small class="field-hint resend-cooldown" id="resend-cooldown"
                   data-seconds="{{ $cooldown }}" aria-live="polite">
                You can resend in {{ $cooldown }}s
            </small>
        @else
            <small class="field-hint resend-cooldown" id="resend-cooldown" data-seconds="0" aria-live="polite"></small>
        @endif
    </form>

    <p class="auth-alt-link muted">
        Trouble receiving the code? <a href="{{ route('login') }}">Back to sign in</a>
    </p>
</div>
@endsection
