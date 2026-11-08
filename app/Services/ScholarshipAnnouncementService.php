<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\SendNotificationEmail;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Announces an open scholarship to the students who qualify for it.
 *
 * Eligibility is decided by the same rule engine used on the student side
 * ({@see ScholarshipEligibilityService}), so a student is only emailed about
 * scholarships they can actually apply to. Delivery reuses the queued
 * {@see SendNotificationEmail} pipeline, which means the in-app notification
 * and the email are always created together.
 */
class ScholarshipAnnouncementService
{
    public function __construct(
        private ScholarshipEligibilityService $eligibility,
        private NotificationService $notifications,
    ) {}

    /**
     * Notify every active student who is eligible for the scholarship.
     *
     * The duplicate guard in NotificationService means a student is never
     * notified twice for the same scholarship while the first notification
     * is still unread.
     *
     * @return int Number of students notified.
     */
    public function announce(Scholarship $scholarship): int
    {
        return $this->notify($scholarship, $this->eligibleStudents($scholarship));
    }

    /**
     * Notify only eligible students who have never received an announcement
     * for this scholarship.
     *
     * Used when staff edit an already-open scholarship: broadening the
     * criteria reaches newly qualified students without emailing everyone
     * who was already told about it.
     *
     * @return int Number of students notified.
     */
    public function announceToNewlyEligible(Scholarship $scholarship): int
    {
        $alreadyNotified = Notification::query()
            ->where('type', NotificationType::SCHOLARSHIP_ANNOUNCEMENT->value)
            ->where('related_type', 'Scholarship')
            ->where('related_id', $scholarship->id)
            ->pluck('user_id')
            ->all();

        $recipients = $this->eligibleStudents($scholarship)
            ->reject(fn (User $student) => in_array($student->id, $alreadyNotified, true))
            ->values();

        return $this->notify($scholarship, $recipients);
    }

    /**
     * Automatically notify a single student about every currently open
     * scholarship they now qualify for but have not been told about yet.
     *
     * Called after the student's eligibility inputs change (profile edit,
     * enrollment verification), so a student who becomes eligible hears about
     * it immediately without any staff action.
     *
     * @return int Number of scholarships announced to the student.
     */
    public function announceToStudent(StudentProfile $profile): int
    {
        $student = $profile->user;

        if (! $student || ! $student->isActive()) {
            return 0;
        }

        $alreadyNotified = Notification::query()
            ->where('user_id', $student->id)
            ->where('type', NotificationType::SCHOLARSHIP_ANNOUNCEMENT->value)
            ->where('related_type', 'Scholarship')
            ->pluck('related_id')
            ->all();

        $query = Scholarship::query()->open();

        if ($alreadyNotified !== []) {
            $query->whereNotIn('id', $alreadyNotified);
        }

        $newlyEligible = $query->get()->filter(
            fn (Scholarship $scholarship) => $this->eligibility->evaluate($profile, $scholarship)->isEligible()
        );

        $announced = 0;

        foreach ($newlyEligible as $scholarship) {
            $announced += $this->notify($scholarship, collect([$student]));
        }

        return $announced;
    }

    /**
     * Active students whose profile satisfies every mandatory criterion.
     *
     * @return Collection<int, User>
     */
    private function eligibleStudents(Scholarship $scholarship): Collection
    {
        $students = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', Role::STUDENT))
            ->with('studentProfile')
            ->get();

        return $students->filter(
            fn (User $student) => $student->studentProfile !== null
                && $this->eligibility->evaluate($student->studentProfile, $scholarship)->isEligible()
        )->values();
    }

    /**
     * Persist an in-app notification and queue the matching email for each
     * recipient.
     *
     * @param  Collection<int, User>  $recipients
     */
    private function notify(Scholarship $scholarship, Collection $recipients): int
    {
        if ($recipients->isEmpty()) {
            return 0;
        }

        $deadline = $scholarship->application_deadline?->format('F j, Y');

        $message = "Based on your student profile, you are eligible for the scholarship "
            ."'{$scholarship->title}' by {$scholarship->provider}."
            .($deadline ? " Applications are open until {$deadline}." : ' Applications are now open.');

        $this->notifications->notifyMultiple(
            $recipients->all(),
            NotificationType::SCHOLARSHIP_ANNOUNCEMENT,
            'You Are Eligible for a Scholarship',
            $message,
            'Scholarship',
            $scholarship->id,
            [
                'reference' => $scholarship->title,
                'reference_label' => 'Scholarship',
                'status' => 'Open for Application',
                'next_step' => 'Log in to review the requirements and submit your application before the deadline.',
                'url' => route('student.scholarships.show', $scholarship->id),
            ],
            sendEmail: true,
        );

        return $recipients->count();
    }
}
