@extends('layouts.app')
@section('title', 'Request ' . $studentServiceRequest->request_number . ' - ISU OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="{{ route('student.services.index') }}" style="color: #4b5563; text-decoration: none; font-size: 0.9rem;">&larr; Back to My Requests</a>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.5rem;">Request: {{ $studentServiceRequest->request_number }}</h2>
        </div>

        @can('cancel', $studentServiceRequest)
            <form action="{{ route('student.services.cancel', $studentServiceRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn" style="background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Cancel Request</button>
            </form>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f8d7da; color: #721c24; border-radius: 6px;">{{ session('error') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Left: Details and Documents -->
        <div>
            <!-- Overview -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.15rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Service Details</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="color: #64748b; display: block; font-size: 0.8rem;">Category:</span>
                        <strong>{{ $studentServiceRequest->serviceType->name }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 0.8rem;">Current Status:</span>
                        @php
                            $badgeStyles = match($studentServiceRequest->status) {
                                'submitted' => 'background: #e0f2fe; color: #0369a1;',
                                'processing' => 'background: #fef3c7; color: #92400e;',
                                'requires_information' => 'background: #fee2e2; color: #991b1b;',
                                'completed' => 'background: #dcfce7; color: #166534;',
                                'cancelled' => 'background: #f3f4f6; color: #4b5563;',
                                default => 'background: #f3f4f6; color: #4b5563;',
                            };
                        @endphp
                        <span style="display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; {{ $badgeStyles }}">
                            {{ ucfirst(str_replace('_', ' ', $studentServiceRequest->status)) }}
                        </span>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 0.8rem;">Submitted At:</span>
                        <span>{{ $studentServiceRequest->submitted_at ? $studentServiceRequest->submitted_at->format('M d, Y h:i A') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span style="color: #64748b; display: block; font-size: 0.8rem;">Completed At:</span>
                        <span>{{ $studentServiceRequest->completed_at ? $studentServiceRequest->completed_at->format('M d, Y h:i A') : '—' }}</span>
                    </div>
                </div>

                @if($studentServiceRequest->remarks)
                    <div style="background: #fef9c3; border-left: 4px solid #ca8a04; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
                        <strong style="color: #854d0e; font-size: 0.85rem; text-transform: uppercase;">Official Remarks:</strong>
                        <p style="margin: 0.25rem 0 0 0; color: #713f12; font-size: 0.9rem;">{{ $studentServiceRequest->remarks }}</p>
                    </div>
                @endif

                <div style="border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                    <strong style="color: #475569; font-size: 0.85rem; display: block; margin-bottom: 0.5rem;">Your Request Description:</strong>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; font-size: 0.9rem; line-height: 1.5; color: #334155; white-space: pre-line;">{{ $studentServiceRequest->description }}</div>
                </div>
            </div>

            <!-- Documents -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
                <h3 style="font-size: 1.15rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Attached Supporting Documents</h3>

                <div style="margin-bottom: 1.5rem;">
                    @forelse($studentServiceRequest->documents as $doc)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 0.5rem;">
                            <div>
                                <strong style="display: block; font-size: 0.9rem; color: #1e293b;">{{ $doc->requirement?->name ?? 'Supporting File' }}</strong>
                                <small style="color: #64748b;">{{ $doc->original_filename }} ({{ number_format($doc->file_size / 1024, 1) }} KB)</small>
                            </div>
                            <a href="{{ route('student.services.documents.download', [$studentServiceRequest, $doc]) }}" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none;">Download</a>
                        </div>
                    @empty
                        <p style="color: #64748b; font-size: 0.9rem; margin: 0;">No documents attached to this request.</p>
                    @endforelse
                </div>

                @if($studentServiceRequest->status === 'requires_information')
                    <div style="background: #fffbeb; border: 1px dashed #f59e0b; border-radius: 6px; padding: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 600; color: #b45309; margin-bottom: 0.5rem;">Upload Requested Additional Document</h4>
                        <form action="{{ route('student.services.documents.store', $studentServiceRequest) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Select Requirement:</label>
                                <select name="service_type_requirement_id" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem;" required>
                                    @foreach($studentServiceRequest->serviceType->requirements as $req)
                                        <option value="{{ $req->id }}">{{ $req->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="margin-bottom: 0.75rem;">
                                <input type="file" name="file" style="display: block; width: 100%; font-size: 0.85rem;" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.85rem;">Upload File</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Status History Timeline -->
        <div>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #1e3a8a; margin-bottom: 1rem;">Status Timeline</h3>

                <div style="border-left: 2px solid #e2e8f0; padding-left: 1rem; margin-left: 0.5rem;">
                    @foreach($studentServiceRequest->histories as $history)
                        <div style="position: relative; margin-bottom: 1.25rem;">
                            <span style="position: absolute; left: -1.4rem; top: 0.25rem; width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                            <strong style="display: block; font-size: 0.85rem; color: #1e293b;">{{ ucfirst(str_replace('_', ' ', $history->new_status)) }}</strong>
                            <small style="color: #64748b; font-size: 0.75rem;">{{ $history->created_at ? $history->created_at->format('M d, Y h:i A') : 'N/A' }}</small>
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