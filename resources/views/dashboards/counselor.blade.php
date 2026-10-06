@extends('layouts.app')
@section('title', 'Counselor Dashboard')

@section('content')
    @php($user = Auth::user())
    @include('dashboards._common', ['user' => $user])

    <section class="card">
        <h3>Counseling</h3>
        <p class="muted">Future modules will appear here.</p>
        <ul class="module-list">
            <li><span class="disabled-link">Guidance Appointments</span> <em>(coming soon)</em></li>
            <li><span class="disabled-link">Counseling Requests</span> <em>(coming soon)</em></li>
        </ul>
    </section>
@endsection