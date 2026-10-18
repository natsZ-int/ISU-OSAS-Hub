<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\GuidanceSchedulingSettings;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GuidanceController extends Controller
{
    // ── Appointment Types ───────────────────────────

    public function types(): View
    {
        $types = GuidanceAppointmentType::orderBy('display_order')->get();
        return view('admin.guidance.types', compact('types'));
    }

    public function storeType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'display_order'    => 'nullable|integer|min:0',
        ]);

        $type = GuidanceAppointmentType::create(array_merge($data, ['is_active' => true]));

        AuditLogService::log(
            action: 'Appointment Type Created',
            module: 'Guidance Scheduling',
            recordType: 'GuidanceAppointmentType',
            recordId: $type->id,
            description: "Created appointment type '{$type->name}'.",
            request: $request,
        );

        return back()->with('status', 'Appointment type created.');
    }

    public function updateType(Request $request, GuidanceAppointmentType $type): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'display_order'    => 'nullable|integer|min:0',
        ]);

        $type->update($data);

        AuditLogService::log(
            action: 'Appointment Type Updated',
            module: 'Guidance Scheduling',
            recordType: 'GuidanceAppointmentType',
            recordId: $type->id,
            description: "Updated appointment type '{$type->name}'.",
            request: $request,
        );

        return back()->with('status', 'Appointment type updated.');
    }

    public function toggleType(GuidanceAppointmentType $type, Request $request): RedirectResponse
    {
        $type->update(['is_active' => ! $type->is_active]);

        AuditLogService::log(
            action: 'Appointment Type ' . ($type->is_active ? 'Activated' : 'Deactivated'),
            module: 'Guidance Scheduling',
            recordType: 'GuidanceAppointmentType',
            recordId: $type->id,
            description: "Appointment type '{$type->name}' " . ($type->is_active ? 'activated' : 'deactivated') . '.',
            request: $request,
        );

        return back()->with('status', 'Appointment type ' . ($type->is_active ? 'activated' : 'deactivated') . '.');
    }

    // ── Scheduling Settings ─────────────────────────

    public function settings(): View
    {
        $settings = GuidanceSchedulingSettings::getActive();
        return view('admin.guidance.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'office_start_time'                  => 'required|date_format:H:i',
            'office_end_time'                    => 'required|date_format:H:i|after:office_start_time',
            'default_appointment_duration_minutes' => 'required|integer|min:5|max:480',
            'minimum_booking_notice_minutes'       => 'required|integer|min:0',
            'student_cancellation_notice_minutes'  => 'required|integer|min:0',
            'maximum_future_booking_days'          => 'required|integer|min:1|max:365',
        ]);

        $settings = GuidanceSchedulingSettings::getActive();
        $settings->update(array_merge([
            'office_start_time'   => $data['office_start_time'] . ':00',
            'office_end_time'     => $data['office_end_time'] . ':00',
            'default_appointment_duration_minutes' => $data['default_appointment_duration_minutes'],
            'minimum_booking_notice_minutes'       => $data['minimum_booking_notice_minutes'],
            'student_cancellation_notice_minutes'  => $data['student_cancellation_notice_minutes'],
            'maximum_future_booking_days'          => $data['maximum_future_booking_days'],
            'updated_by'                           => $request->user()->id,
        ]));

        AuditLogService::log(
            action: 'Scheduling Settings Updated',
            module: 'Guidance Scheduling',
            recordType: 'GuidanceSchedulingSettings',
            recordId: $settings->id,
            description: 'Updated global scheduling settings.',
            request: $request,
        );

        return back()->with('status', 'Scheduling settings updated.');
    }

    // ── All Appointments ────────────────────────────

    public function appointments(Request $request): View
    {
        $query = GuidanceAppointment::with(['studentProfile', 'counselor', 'appointmentType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('counselor_id')) {
            $query->where('counselor_id', $request->counselor_id);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time')
            ->paginate(15);

        $counselors = User::whereHas('role', fn ($q) => $q->where('name', 'counselor'))->get();

        return view('admin.guidance.appointments', compact('appointments', 'counselors'));
    }
}