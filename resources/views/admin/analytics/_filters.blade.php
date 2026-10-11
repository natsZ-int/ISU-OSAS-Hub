<form method="GET" action="{{ $filterAction ?? route('admin.analytics.index') }}" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
        </div>
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Date To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
        </div>

        @if(!empty($filterOptions['scholarships']) && ($showScholarshipFilter ?? false))
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Scholarship</label>
            <select name="scholarship_id" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">All Scholarships</option>
                @foreach($filterOptions['scholarships'] as $id => $title)
                    <option value="{{ $id }}" {{ ($filters['scholarship_id'] ?? '') == $id ? 'selected' : '' }}>{{ $title }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if(!empty($filterOptions['appointmentTypes']) && ($showAppointmentFilter ?? false))
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Appointment Type</label>
            <select name="appointment_type_id" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">All Types</option>
                @foreach($filterOptions['appointmentTypes'] as $id => $name)
                    <option value="{{ $id }}" {{ ($filters['appointment_type_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if(!empty($filterOptions['serviceTypes']) && ($showServiceFilter ?? false))
        <div>
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem;">Service Type</label>
            <select name="service_type_id" style="padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">All Types</option>
                @foreach($filterOptions['serviceTypes'] as $id => $name)
                    <option value="{{ $id }}" {{ ($filters['service_type_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.45rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">Apply Filters</button>
            <a href="{{ $filterAction ?? route('admin.analytics.index') }}" style="padding: 0.45rem 1rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; text-decoration: none; font-weight: 600;">Clear</a>
        </div>
    </div>

    @if(!empty($filters))
        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
            Active filters:
            @foreach($filters as $key => $val)
                <span style="background: #e0f2fe; color: #0369a1; padding: 0.1rem 0.4rem; border-radius: 3px; margin-left: 0.25rem;">{{ $key }}: {{ $val }}</span>
            @endforeach
        </div>
    @endif
</form>