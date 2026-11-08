@if ($users->count() > 0)
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Student No.</th>
                <th>Enrollment</th>
                <th>Status</th>
                <th>Created</th>
                <th class="actions-heading">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="badge badge-{{ $user->role?->name }}">
                        {{ $user->role?->label ?? ucfirst($user->role?->name ?? 'N/A') }}
                    </span>
                </td>
                <td>{{ $user->studentProfile?->student_number ?? '—' }}</td>
                <td>
                    @if ($user->studentProfile)
                        @php $es = \App\Enums\EnrollmentStatus::tryFrom($user->studentProfile->enrollment_status ?? 'unknown'); @endphp
                        <span class="badge badge-{{ $es?->value ?? 'unknown' }}">
                            {{ $es?->label() ?? 'Unknown' }}
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if ($user->is_active)
                        <span class="badge badge-active">Active</span>
                    @else
                        <span class="badge badge-inactive">Inactive</span>
                    @endif
                </td>
                <td>{{ $user->created_at->format('M d, Y') }}</td>
                <td class="actions-cell actions-cell--icons">
                    <div class="action-group" role="group" aria-label="Actions for {{ $user->name }}">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="action-btn"
                           title="Edit {{ $user->name }}"
                           aria-label="Edit {{ $user->name }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                            </svg>
                        </a>

                        @if ($user->is_active)
                            @if ($user->id !== auth()->id() && (! $user->isSystemAdmin() || auth()->user()->isSystemAdmin()))
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                      class="inline-form"
                                      onsubmit="return confirm({{ Js::from('Deactivate '.$user->name.'?') }})">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn action-btn-danger"
                                            title="Deactivate {{ $user->name }}"
                                            aria-label="Deactivate {{ $user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                            <path d="M12 2v10"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}"
                                  class="inline-form"
                                  onsubmit="return confirm({{ Js::from('Activate '.$user->name.'?') }})">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-btn action-btn-success"
                                        title="Activate {{ $user->name }}"
                                        aria-label="Activate {{ $user->name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                        <path d="M12 2v10"></path>
                                    </svg>
                                </button>
                            </form>
                        @endif

                        @if (! $user->isSystemAdmin() || auth()->user()->isSystemAdmin())
                            <a href="{{ route('admin.users.reset-password', $user) }}"
                               class="action-btn"
                               title="Reset password for {{ $user->name }}"
                               aria-label="Reset password for {{ $user->name }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <circle cx="7.5" cy="15.5" r="4.5"></circle>
                                    <path d="m11 12 8-8"></path>
                                    <path d="m16 4 4 4"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="pagination-wrapper">
    {{ $users->links() }}
</div>
@else
    <p class="empty-state">No users found matching your criteria.</p>
@endif
