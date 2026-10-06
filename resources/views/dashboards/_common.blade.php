<section class="card">
    <h2>Welcome, {{ $user->name }}</h2>
    <ul class="user-meta">
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Role:</strong> {{ $user->role?->label ?? $user->role?->name }}</li>
    </ul>
</section>