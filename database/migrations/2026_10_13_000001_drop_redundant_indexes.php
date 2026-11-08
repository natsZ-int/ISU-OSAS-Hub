<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove redundant indexes identified during the performance review.
 *
 * Two categories are removed:
 *
 * 1. Exact duplicates — the same column set indexed twice under different
 *    names (e.g. `audit_logs.action` had both `audit_logs_action_index` and
 *    `idx_audit_action`). One copy is always kept.
 *
 * 2. Single-column indexes fully covered by the leftmost column of another
 *    index on the same table (B-tree prefix rule), e.g. `status` when a
 *    (status, application_start_date, application_deadline) index exists.
 *
 * Every drop is guarded with Schema::hasIndex() so the migration is safe on
 * databases where an index was never created, and down() recreates exactly
 * what up() removed so migration rollbacks stay symmetric.
 *
 * Read behaviour is unchanged: every query pattern that used a dropped index
 * can still use the covering index documented below.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, string[]>>
     *                                             table => [index name => columns (for restoration in down())]
     */
    private const REDUNDANT = [
        'scholarships' => [
            // duplicate of scholarships_type_idx
            'idx_scholarships_type' => ['scholarship_type'],
            // duplicate of scholarships_created_at_idx
            'idx_scholarships_created_at' => ['created_at'],
            // covered by idx_scholarships_status_dates (status, ...)
            'scholarships_status_index' => ['status'],
        ],
        'audit_logs' => [
            // duplicate of audit_logs_action_index
            'idx_audit_action' => ['action'],
        ],
        'scholarship_applications' => [
            // duplicate of scholarship_applications_status_index
            'idx_sa_status' => ['status'],
            // duplicate of scholarship_applications_submitted_at_index
            'idx_sa_submitted_at' => ['submitted_at'],
            // covered by uniq_student_scholarship (student_profile_id, scholarship_id)
            'scholarship_applications_student_profile_id_index' => ['student_profile_id'],
            // covered by idx_sa_scholarship_status (scholarship_id, status)
            'scholarship_applications_scholarship_id_index' => ['scholarship_id'],
        ],
        'student_service_requests' => [
            // duplicate of student_service_requests_status_index
            'idx_ssr_status' => ['status'],
            // duplicate of student_service_requests_submitted_at_index
            'idx_ssr_submitted_at' => ['submitted_at'],
            // duplicate of student_service_requests_service_type_id_index
            'idx_ssr_service_type' => ['service_type_id'],
        ],
        'guidance_appointments' => [
            // duplicate of guidance_appointments_status_index
            'idx_ga_status' => ['status'],
            // duplicate of idx_appt_counselor_date_status
            'idx_counselor_appointments_dashboard' => ['counselor_id', 'appointment_date', 'status'],
            // duplicate of idx_appt_student_date_status (index added manually, may be absent)
            'idx_student_appointments_dashboard' => ['student_profile_id', 'appointment_date', 'status'],
            // covered by guidance_appointments_counselor_id_appointment_date_index
            'idx_ga_counselor' => ['counselor_id'],
            // covered by idx_appt_date_times (appointment_date, start_time, end_time)
            'idx_ga_appointment_date' => ['appointment_date'],
            // covered by guidance_appointments_student_profile_id_appointment_date_index
            'guidance_appointments_student_profile_id_index' => ['student_profile_id'],
        ],
        'counselor_availabilities' => [
            // covered by counselor_availabilities_counselor_id_day_of_week_index
            'counselor_availabilities_counselor_id_index' => ['counselor_id'],
        ],
        'notifications' => [
            // covered by idx_notif_user_read (user_id, read_at)
            'notifications_user_id_index' => ['user_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::REDUNDANT as $table => $indexes) {
            foreach ($indexes as $index => $columns) {
                if (Schema::hasIndex($table, $index)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($index) {
                        $blueprint->dropIndex($index);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::REDUNDANT as $table => $indexes) {
            foreach ($indexes as $index => $columns) {
                if (! Schema::hasIndex($table, $index)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($index, $columns) {
                        $blueprint->index($columns, $index);
                    });
                }
            }
        }
    }
};
