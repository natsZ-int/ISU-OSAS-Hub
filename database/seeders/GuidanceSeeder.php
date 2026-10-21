<?php

namespace Database\Seeders;

use App\Models\GuidanceAppointmentType;
use App\Models\GuidanceSchedulingSettings;
use Illuminate\Database\Seeder;

class GuidanceSeeder extends Seeder
{
    public function run(): void
    {
        // Create default appointment types
        $types = [
            ['name' => 'Academic Concern',     'description' => 'Academic performance and study-related issues', 'duration_minutes' => 30, 'display_order' => 1],
            ['name' => 'Personal Concern',      'description' => 'Personal and emotional well-being',            'duration_minutes' => 45, 'display_order' => 2],
            ['name' => 'Career Guidance',       'description' => 'Career planning and exploration',              'duration_minutes' => 30, 'display_order' => 3],
            ['name' => 'General Consultation',  'description' => 'General guidance and consultation',            'duration_minutes' => 30, 'display_order' => 4],
        ];

        foreach ($types as $type) {
            GuidanceAppointmentType::firstOrCreate(
                ['name' => $type['name']],
                array_merge($type, ['is_active' => true])
            );
        }

        // Create default scheduling settings
        GuidanceSchedulingSettings::firstOrCreate(
            ['is_active' => true],
            [
                'office_start_time' => '08:00:00',
                'office_end_time' => '17:00:00',
                'default_appointment_duration_minutes' => 30,
                'minimum_booking_notice_minutes' => 60,
                'student_cancellation_notice_minutes' => 60,
                'maximum_future_booking_days' => 30,
            ]
        );
    }
}
