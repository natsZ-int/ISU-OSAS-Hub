@extends('layouts.app')
@section('title', 'Add Service Type - Admin OSAS Hub')

@section('content')
<div class="container py-4" style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.service-types.index') }}" style="color: #4b5563; text-decoration: none; font-size: 0.9rem;">&larr; Back to Service Types</a>
    </div>

    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem;">
        <h2 style="font-size: 1.4rem; font-weight: 700; color: #1e3a8a; margin-bottom: 1.5rem;">Create Service Type</h2>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #f8d7da; color: #721c24; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.service-types.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Name <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;" required>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Display Order <span style="color: #dc2626;">*</span></label>
                    <input type="number" name="display_order" value="{{ old('display_order', 0) }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;" required>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Status <span style="color: #dc2626;">*</span></label>
                    <select name="is_active" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;" required>
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Description</label>
                <textarea name="description" rows="3" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;">{{ old('description') }}</textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.25rem;">Instructions for Students</label>
                <textarea name="instructions" rows="4" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem;">{{ old('instructions') }}</textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: #374151; margin: 0;">Supporting Document Requirements</h3>
                <button type="button" id="add-req-btn" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">+ Add Requirement</button>
            </div>

            <div id="requirements-container"></div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <a href="{{ route('admin.service-types.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.6rem 1.25rem;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Save Service Type</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('add-req-btn').addEventListener('click', function() {
    const container = document.getElementById('requirements-container');
    const index = container.children.length;
    const row = document.createElement('div');
    row.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 0.75rem; display: grid; grid-template-columns: 2fr 2fr 1fr 1fr auto; gap: 0.75rem; align-items: end;';
    row.innerHTML = `
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600;">Name *</label>
            <input type="text" name="requirements[${index}][name]" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem;" required>
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600;">Description</label>
            <input type="text" name="requirements[${index}][description]" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem;">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600;">Required? *</label>
            <select name="requirements[${index}][is_required]" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem;" required>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 600;">Order *</label>
            <input type="number" name="requirements[${index}][display_order]" value="${index + 1}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.4rem;" required>
        </div>
        <div>
            <button type="button" class="btn" style="background: #ef4444; color: #fff; padding: 0.4rem 0.6rem; font-size: 0.8rem; border-radius: 4px;" onclick="this.parentElement.parentElement.remove();">&times;</button>
        </div>
    `;
    container.appendChild(row);
});
</script>
@endsection