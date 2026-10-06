@extends('layouts.app')
@section('title', 'Student Dashboard')

@section('content')
    @php($user = Auth::user())
    @include('dashboards._common', ['user' => $user])

    <section class="card">
        <h3>Student Services</h3>
        <p class="muted">Future modules will appear here.</p>
        <ul class="module-list">
            <li><span class="disabled-link">Scholarship Applications</span> <em>(coming soon)</em></li>
            <li><span class="disabled-link">Guidance Appointments</span> <em>(coming soon)</em></li>
            <li><span class="disabled-link">Service Requests</span> <em>(coming soon)</em></li>
            <li><span class="disabled-link">My Records</span> <em>(coming soon)</em></li>
        </ul>
    </section>
@endsection
