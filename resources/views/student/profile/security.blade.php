@extends('layouts.app')

@section('title', 'Profile Security — ISU OSAS Hub')

@section('content')
    <x-page-header
        title="Profile Security"
        subtitle="Protect sensitive profile information with a passcode.">
        <x-slot:actions>
            <a href="{{ route('student.profile.show') }}" class="btn btn-outline-dark">← Back to Profile</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert type="error">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="verification-layout">
        <x-card>
            <h3 class="section-heading">Passcode Protection</h3>

            @if ($hasPasscode)
                <p class="muted" style="font-size:.9rem;">
                    Passcode protection is currently
                    <strong>{{ $isUnlocked ? 'unlocked for this session' : 'locked' }}</strong>.
                    Protected details include your contact number, address, GWA, and family income.
                </p>

                @if ($isUnlocked)
                    <form method="POST" action="{{ route('student.profile.lock') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-outline-dark">Lock Profile Now</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('student.profile.unlock') }}" class="filter-form" style="max-width:420px;">
                        @csrf
                        <div class="form-group">
                            <label for="passcode">Profile Passcode</label>
                            <input type="password" id="passcode" name="passcode" autocomplete="current-password"
                                   inputmode="numeric" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Unlock Profile</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('student.profile.passcode.destroy') }}" style="margin-top:1rem;"
                      onsubmit="return confirm('Remove passcode protection from your profile?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Remove Passcode Protection</button>
                </form>
            @else
                <p class="muted" style="font-size:.9rem;">
                    No passcode is configured yet. Add one to hide sensitive details until the passcode is entered.
                </p>
            @endif
        </x-card>

        <x-card>
            <h3 class="section-heading">{{ $hasPasscode ? 'Change Passcode' : 'Set a Passcode' }}</h3>

            <form method="POST" action="{{ route('student.profile.passcode.update') }}" class="filter-form">
                @csrf
                @method('PUT')

                @if ($hasPasscode && ! $isUnlocked)
                    <div class="form-group">
                        <label for="current_passcode">Current Passcode</label>
                        <input type="password" id="current_passcode" name="current_passcode"
                               autocomplete="current-password" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    </div>
                @endif

                <div class="form-group">
                    <label for="passcode">New Passcode</label>
                    <input type="password" id="passcode" name="passcode" autocomplete="new-password"
                           minlength="{{ \App\Services\StudentProfilePasscodeService::MIN_LENGTH }}"
                           maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    <small class="field-hint">Use 4–32 characters. Do not reuse your account password.</small>
                </div>

                <div class="form-group">
                    <label for="passcode_confirmation">Confirm New Passcode</label>
                    <input type="password" id="passcode_confirmation" name="passcode_confirmation"
                           autocomplete="new-password" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                </div>

                <button type="submit" class="btn btn-primary">{{ $hasPasscode ? 'Change Passcode' : 'Save Passcode' }}</button>
            </form>
        </x-card>
    </div>
@endsection
