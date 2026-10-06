@extends('layouts.app')
@section('title', 'Submit Service Request - ISU OSAS Hub')

@section('content')
<div class="container py-4" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('student.services.catalog') }}" style="color: #4b5563; text-decoration: none; font-size: 0.9rem;">&larr; Back to Catalog</a>
    </div>

    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem;">
        <h2 style="font-size: 1.4rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.5rem;">{{ $serviceType->name }}</h2>
        <p style="color: #4b5563; margin-bottom: 1.5rem;">{{ $serviceType->description }}</p>

        @if($serviceType->instructions)
            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem; color: #1e40af;">
                <strong>Instructions:</strong>
                <p style="margin: 0.25rem 0 0 0; white-space: pre-line;">{{ $serviceType->instructions }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f8d7da; color: #721c24; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('student.services.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="service_type_id" value="{{ $serviceType->id }}">

            <div style="margin-bottom: 1.5rem;">
                <label for="description" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1f2937;">Detailed Request Description <span style="color: #dc2626;">*</span></label>
                <textarea name="description" id="description" rows="5" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 0.75rem; font-family: inherit; font-size: 0.95rem;" placeholder="State the purpose, required details, or specific context for this request..." required>{{ old('description') }}</textarea>
            </div>

            @if($serviceType->requirements->count() > 0)
                <div style="margin-top: 2rem; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Required Supporting Documents</h3>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem;">Accepted formats: PDF, JPG, JPEG, PNG (Max: 5 MB each).</p>

                    @foreach($serviceType->requirements as $req)
                        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">
                                {{ $req->name }}
                                @if($req->is_required)
                                    <span style="color: #dc2626;">* (Required)</span>
                                @else
                                    <span style="color: #6b7280; font-weight: normal;">(Optional)</span>
                                @endif
                            </label>
                            @if($req->description)
                                <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.5rem;">{{ $req->description }}</p>
                            @endif
                            <input type="file" name="requirement_{{ $req->id }}" style="display: block; width: 100%; font-size: 0.9rem;" @if($req->is_required) required @endif>
                        </div>
                    @endforeach
                </div>
            @endif

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <a href="{{ route('student.services.catalog') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.6rem 1.25rem;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection