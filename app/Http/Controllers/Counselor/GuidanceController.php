<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\CounselorAvailability;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceSchedulingSettings;
use App\Services\AuditLogService;
use App\Services\GuidanceAppointmentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuidanceController extends Controller
{
    public function __construct(
        protected GuidanceAppointmentWorkflowService $workflowService,
    ) {}

    // ── Availability ──────────────────────────────────────

    public function availability(Request $request): View
    {
        $counselor = $request->user();
        $availabilities = CounselorAvailability::where('counselor_id', $counselor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $settings = GuidanceSchedulingSettings::getActive();

        return view('counselor.guidance.availability', compact('availabilities', 'settings'));
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $counselor = $request->user();
        $settings = GuidanceSchedulingSettings::getActive();
        $startTime = $request->start_time.':00';
        $endTime = $request->end_time.':00';

        if ($startTime < $settings->office_start_time || $endTime > $settings->office_end_time) {
            return back()->withErrors(['time' => 'Availability must be within office hours.']);
        }

        $overlaps = CounselorAvailability::where('counselor_id', $counselor->id)
            ->where('day_of_week', $request->day_of_week)
            ->active()
            ->get()
            ->contains(fn ($a) => $a->overlapsWith($startTime, $endTime));

        if ($overlaps) {
            return back()->withErrors(['time' => 'This time overlaps with an existing availability window.']);
        }

        CounselorAvailability::create([
            'counselor_id' => $counselor->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_available' => true,
        ]);

        AuditLogService::log(
            action: 'Availability Created',
            module: 'Guidance Scheduling',
            recordType: 'CounselorAvailability',
            description: "Counselor added availability on day {$request->day_of_week}.",
            userId: $counselor->id,
        );

        return back()->with('status', 'Availability added.');
    }

    public function destroyAvailability(Request $request, CounselorAvailability $availability): RedirectResponse
    {
        $counselor = $request->user();
        abort_unless((int) $availability->counselor_id === (int) $counselor->id, 403);

        $availability->update(['is_available' => false]);

        AuditLogService::log(
            action: 'Availability Deactivated',
            module: 'Guidance Scheduling',
            recordType: 'CounselorAvailability',
            recordId: $availability->id,
            description: "Counselor deactivated availability on day {$availability->day_of_week}.",
            userId: $counselor->id,
        );

        return back()->with('status', 'Availability deactivated.');
    }

    // ── Appointments ──────────────────────────────────────

    public function appointments(Request $request): View
    {
        $counselor = $request->user();

        $query = GuidanceAppointment::where('counselor_id', $counselor->id)
            ->with(['studentProfile.user', 'appointmentType']);

        if ($search = $request->filled('search') ? trim((string) $request->input('search')) : null) {
            $like = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function ($q) use ($like) {
                $q->where('appointment_type', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('studentProfile', function ($student) use ($like) {
                        $student->where('student_number', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    })
                    ->orWhereHas('appointmentType', fn ($type) => $type->where('name', 'like', $like));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return view('counselor.guidance.appointments', compact('appointments'));
    }

    public function showAppointment(Request $request, GuidanceAppointment $appointment): View
    {
        $counselor = $request->user();
        abort_unless((int) $appointment->counselor_id === (int) $counselor->id, 403);

        $appointment->load(['studentProfile', 'appointmentType']);
        $allowedTransitions = $this->workflowService->getAllowedTransitions($appointment);

        return view('counselor.guidance.show', compact('appointment', 'allowedTransitions'));
    }

    public function confirm(Request $request, GuidanceAppointment $appointment): RedirectResponse
    {
        $counselor = $request->user();
        abort_unless((int) $appointment->counselor_id === (int) $counselor->id, 403);

        try {
            $this->workflowService->transition(
                $appointment,
                GuidanceAppointment::STATUS_CONFIRMED,
                $counselor
            );

            return back()->with('status', 'Appointment confirmed.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, GuidanceAppointment $appointment): RedirectResponse
    {
        $counselor = $request->user();
        abort_unless((int) $appointment->counselor_id === (int) $counselor->id, 403);

        $request->validate(['rejection_reason' => 'required|string|min:5']);

        try {
            $this->workflowService->transition(
                $appointment,
                GuidanceAppointment::STATUS_REJECTED,
                $counselor,
                $request->rejection_reason
            );

            return back()->with('status', 'Appointment rejected.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }
    }

    public function complete(Request $request, GuidanceAppointment $appointment): RedirectResponse
    {
        $counselor = $request->user();
        abort_unless((int) $appointment->counselor_id === (int) $counselor->id, 403);

        try {
            $this->workflowService->transition(
                $appointment,
                GuidanceAppointment::STATUS_COMPLETED,
                $counselor
            );

            return back()->with('status', 'Appointment marked as completed.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }
    }
}
