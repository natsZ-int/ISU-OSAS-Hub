@extends('layouts.app')

@section('title', 'Edit Student Profile — ISU OSAS Hub')

@section('content')
    <x-page-header
        title="Edit Student Profile"
        subtitle="{{ $profile->student_number }} — {{ trim($profile->first_name.' '.$profile->last_name) }}">
        <x-slot:actions>
            <a href="{{ route('admin.student-profiles.show', $profile) }}" class="btn btn-outline-dark">← Back to Profile</a>
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
        <form method="POST" action="{{ route('admin.student-profiles.update', $profile) }}">
            @csrf
            @method('PUT')

            @include('partials.student-profile-fields', [
                'profile' => $profile,
                'isAdmin' => true,
                'enrollmentStatuses' => $enrollmentStatuses,
            ])

            <div class="button-row" style="margin-top:1.25rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.student-profiles.show', $profile) }}" class="btn btn-outline-dark">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
