@extends('layouts.app')
@section('title', 'Manage Request ' . $studentServiceRequest->request_number . ' - Admin OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.student-service-requests.index') }}" style="color: #4b5563; text-decoration: none; font-size: 0.9rem;">&larr; Back to Requests</a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.5rem;">Request: {{ $studentServiceRequest->request_number }}</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f8d7da; color: #721c24; border-radius: 6px;">{{ session('error') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Left: Student Info, Description, Documents, Status Action -->
        <div>
            <!-- Student Information Card -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Student Details</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                    <div>
                        <span style="color: #64748b; font-size: 0.8rem; display: block;">Student Name:</span>
                        <strong>{{ $studentServiceRequest->studentProfile->first_name }} {{ $studentServiceRequest->studentProfile->last_name }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.8rem; display: block;">Student Number:</span>
                        <strong>{{ $studentServiceRequest->studentProfile->student_number }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.8rem; display: block;">Program & Year:</span>
                        <span>{{ $studentServiceRequest->studentProfile->program }} - Year {{ $studentServiceRequest->studentProfile->year_level }}</span>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.8rem; display: block;">Email:</span>
                        <span>{{ $studentServiceRequest->studentProfile->user?->email }}</span>
                    </div>
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding-top: 1rem; margin-top: 1rem;">
                    <span style="color: #64748b; font-size: 0.8rem; display: block; margin-bottom: 0.35rem;">Request Description:</span>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; font-size: 0.9rem; line-height: 1.5; color: #334155; white-space: pre-line;">{{ $studentServiceRequest->description }}</div>
                </div>
            </div>

            <!-- Documents -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Supporting Documents Checklist</h3>
                @forelse($studentServiceRequest->documents as $doc)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 0.5rem;">
                        <div>
                            <strong style="display: block; font-size: 0.9rem; color: #1e293b;">{{ $doc->requirement?->name ?? 'Supporting Document' }}</strong>
                            <small style="color: #64748b;">{{ $doc->original_filename }} ({{ number_format($doc->file_size / 1024, 1) }} KB)</small>
                        </div>
                        <a href="{{ route('admin.student-service-requests.documents.download', [$studentServiceRequest, $doc]) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none;">Download File</a>
                    </div>
                @empty
                    <p style="color: #64748b; font-size: 0.9rem; margin: 0;">No document attachments submitted.</p>
                @endforelse
            </div>

            <!-- Workflow Status Controls -->
            @if(count($allowedTransitions) > 0)
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 1.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #92400e; margin-bottom: 1rem;">Update Workflow Status</h3>
                    <form action="{{ route('admin.student-service-requests.transition', $studentServiceRequest) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Select Target State <span style="color: #dc2626;">*</span></label>
                            <select name="status" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;" required>
                                <option value="">-- Choose Next State --</option>
                                @foreach($allowedTransitions as $st)
                                    <option value="{{ $st }}">{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Student-Visible Remarks</label>
                            <textarea name="remarks" rows="3" placeholder="Remarks explaining completion or details needed when requesting additional information..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;"></textarea>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Internal Admin Notes</label>
                            <textarea name="internal_remarks" rows="2" placeholder="Private internal processing note..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.6rem;">Execute Workflow Transition</button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Right: Handler Assignment and Timeline -->
        <div>
            <!-- Handler Assignment Card -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Handler Assignment</h3>
                <div style="margin-bottom: 1rem; font-size: 0.9rem;">
                    <span style="color: #64748b; font-size: 0.8rem; display: block;">Assigned Handler:</span>
                    <strong>{{ $studentServiceRequest->handler?->name ?? 'Unassigned' }}</strong>
                </div>

                @can('assign', $studentServiceRequest)
                    <form action="{{ route('admin.student-service-requests.assign', $studentServiceRequest) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div style="margin-bottom: 0.75rem;">
                            <select name="handled_by" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem; font-size: 0.85rem;" required>
                                <option value="">-- Assign Handler --</option>
                                @foreach($handlers as $h)
                                    <option value="{{ $h->id }}" {{ $studentServiceRequest->handled_by === $h->id ? 'selected' : '' }}>{{ $h->name }} ({{ $h->role?->name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline" style="width: 100%; padding: 0.4rem; font-size: 0.85rem;">Update Handler</button>
                    </form>
                @endcan
            </div>

            <!-- Workflow History Timeline -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Request Timeline</h3>
                <div style="border-left: 2px solid #e2e8f0; padding-left: 1rem; margin-left: 0.5rem;">
                    @foreach($studentServiceRequest->histories as $history)
                        <div style="position: relative; margin-bottom: 1.25rem;">
                            <span style="position: absolute; left: -1.4rem; top: 0.25rem; width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                            <strong style="display: block; font-size: 0.85rem; color: #1e293b;">{{ ucfirst(str_replace('_', ' ', $history->new_status)) }}</strong>
                            <small style="color: #64748b; font-size: 0.75rem;">{{ $history->created_at ? $history->created_at->format('M d, Y h:i A') : 'N/A' }}</small>
                            <small style="display: block; color: #475569; font-size: 0.75rem;">By: {{ $history->user?->name ?? 'System' }}</small>
                            @if($history->remarks)
                                <p style="margin: 0.35rem 0 0 0; font-size: 0.8rem; color: #475569; background: #f8fafc; padding: 0.35rem 0.5rem; border-radius: 4px;">{{ $history->remarks }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection