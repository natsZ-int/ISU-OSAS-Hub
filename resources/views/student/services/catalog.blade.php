@extends('layouts.app')
@section('title', 'OSAS Services Catalog - ISU OSAS Hub')

@section('content')
<div class="container py-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">Student Services Catalog</h2>
            <p style="color: #6c757d; margin: 0;">Browse and request student affairs and support services from OSAS.</p>
        </div>
        <a href="{{ route('student.services.index') }}" class="btn btn-outline" style="padding: 0.5rem 1rem;">My Service Requests</a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f8d7da; color: #721c24; border-radius: 6px;">{{ session('error') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
        @forelse($serviceTypes as $type)
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 600; color: #1e3a8a; margin-bottom: 0.5rem;">{{ $type->name }}</h3>
                    <p style="color: #4b5563; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">{{ $type->description }}</p>

                    @if($type->requirements->count() > 0)
                        <div style="background: #f8fafc; border: 1px solid #edf2f7; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem;">
                            <strong style="font-size: 0.8rem; color: #475569; text-transform: uppercase; display: block; margin-bottom: 0.35rem;">Required Checklist:</strong>
                            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #4b5563;">
                                @foreach($type->requirements as $req)
                                    <li>
                                        {{ $req->name }}
                                        @if($req->is_required)
                                            <span style="color: #dc2626; font-weight: 600;">*</span>
                                        @else
                                            <span style="color: #64748b; font-size: 0.75rem;">(Optional)</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div style="margin-top: 1rem;">
                    <a href="{{ route('student.services.create', $type) }}" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none; padding: 0.6rem 1rem;">Request This Service</a>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 3rem; text-align: center; color: #64748b;">
                @if (request('search'))
                    No services match "{{ request('search') }}".
                @else
                    No service catalog items are available at the moment.
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection