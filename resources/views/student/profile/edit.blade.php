@extends('layouts.app')

@section('title', 'Edit My Profile — ISU OSAS Hub')

@section('content')
    <x-page-header
        title="Edit My Profile"
        subtitle="Update your personal, academic, and financial information.">
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

    <x-card>
        <form method="POST" action="{{ route('student.profile.update') }}">
            @csrf
            @method('PUT')

            @include('partials.student-profile-fields', ['profile' => $profile, 'isAdmin' => false])

            <div class="button-row" style="margin-top:1.25rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('student.profile.show') }}" class="btn btn-outline-dark">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
