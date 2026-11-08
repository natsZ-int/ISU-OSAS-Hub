<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_number' => 'ISU-'.fake()->unique()->numerify('####-#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->lastName(),
            'contact_number' => '09'.fake()->numerify('#########'),
            'college' => 'College of Computing Studies',
            'program' => 'BSIT',
            'year_level' => fake()->numberBetween(1, 4),
            'enrollment_status' => 'enrolled',
            'gwa' => fake()->randomFloat(2, 1.0, 2.5),
            'family_income' => fake()->numberBetween(100000, 400000),
            'address' => fake()->address(),
        ];
    }
}
