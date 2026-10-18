<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceAppointmentType;
use App\Models\User;
use App\Services\GuidanceAppointmentSchedulingService;
use App\Services\GuidanceAppointmentWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GuidanceController extends Controller
{
    public function __construct(
        protected GuidanceAppointmentSchedulingService $schedulingService,
        protected GuidanceAppointmentWorkflowService $workflowService,
    ) {}

    // List available counselors
    public function counselors(): View
    {
        $counselors = User::whereHas('role', fn ($q) => $q->where('name', 'counselor'))
            ->where('is_active', true)
            ->with('availabilities')
            ->get();

        return view('student.guidance.counselors', compact('counselors'));
    }

    // Show available slots for a counselor on a selected date
    public function slots(Request $request, User $counselor): View
    {
        abort_unless($counselor->isCounselor() && $counselor->is_active, 404);

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now()->addDay();

        $typeId = $request->input('type');
        $appointmentType = $typeId ? GuidanceAppointmentType::active()->find($typeId) : null;

        $slots = $this->schedulingService->getAvailableSlots($counselor, $date, $appointmentType);
        $types = GuidanceAppointmentType::active()->get();
        $maxBookingDays = \App\Models\GuidanceSchedulingSettings::getActive()->maximum_future_booking_days;

        return view('student.guidance.slots', compact('counselor', 'date', 'slots', 'types', 'appointmentType', 'maxBookingDays'));
    }

    // Book an appointment
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'counselor_id'        => 'required|exists:users,id',
            'appointment_type_id' => 'required|exists:guidance_appointment_types,id',
            'appointment_date'    => 'required|date|after_or_equal:today',
            'start_time'          => 'required|date_format:H:i',
            'reason'              => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $profile = $user->studentProfile;

        if (! $profile) {
            return back()->withErrors(['profile' => 'Please complete your student profile first.']);
        }

        $counselor = User::findOrFail($request->counselor_id);
        abort_unless($counselor->isCounselor() && $counselor->is_active, 403);

        $appointmentType = GuidanceAppointmentType::active()->findOrFail($request->appointment_type_id);
        $date = Carbon::parse($request->appointment_date);
        $startTime = Carbon::parse($request->start_time)->format('H:i:s');

        try {
            $this->schedulingService->bookAppointment(
                $profile, $counselor, $appointmentType,
                $date, $startTime, $request->reason, $user
            );

            return redirect()->route('student.guidance.appointments')
                ->with('status', 'Appointment requested successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }
    }

    // List student's own appointments
    public function appointments(Request $request): View
    {
        $profile = $request->user()->studentProfile;

        $appointments = GuidanceAppointment::where('student_profile_id', $profile?->id)
            ->with(['counselor', 'appointmentType'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return view('student.guidance.appointments', compact('appointments'));
    }

    // Show appointment detail
    public function show(GuidanceAppointment $appointment): View
    {
        $profile = request()->user()->studentProfile;
        abort_unless($profile && (int) $appointment->student_profile_id === $profile->id, 403);

        $appointment->load(['counselor', 'appointmentType']);

        return view('student.guidance.show', compact('appointment'));
    }

    // Cancel an appointment
    public function cancel(GuidanceAppointment $appointment): RedirectResponse
    {
        $user = request()->user();
        $profile = $user->studentProfile;
        abort_unless($profile && (int) $appointment->student_profile_id === $profile->id, 403);

        try {
            $this->workflowService->transition(
                $appointment,
                GuidanceAppointment::STATUS_CANCELLED,
                $user
            );

            return back()->with('status', 'Appointment cancelled.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }
    }
}