<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpVerificationMail;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function studentRoleId(): int
    {
        return Role::where('name', Role::STUDENT)->first()->id;
    }

    /**
     * Valid registration payload fields related to the student profile.
     */
    private function profileFields(array $overrides = []): array
    {
        return array_merge([
            'college'        => 'College of Engineering',
            'program'        => 'BS Computer Engineering',
            'contact_number' => '09171234567',
            'gwa'            => '1.75',
            'family_income'  => '150000',
            'address'        => '123 Sampaguita St., Isabela',
        ], $overrides);
    }

    public function test_registration_page_is_visible_to_guests(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create Student Account');
    }

    public function test_valid_registration_creates_student_account_and_sends_otp(): void
    {
        Mail::fake();

        $this->post('/register', array_merge([
            'student_number'   => '2026-1001',
            'name'             => 'Juan Dela Cruz',
            'email'            => 'juan@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields()))->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'juan@student.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isStudent());
        $this->assertSame($this->studentRoleId(), $user->role_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->isActive());

        $profile = StudentProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('2026-1001', $profile->student_number);
        $this->assertSame('Juan', $profile->first_name);
        $this->assertSame('Dela Cruz', $profile->last_name);
        $this->assertSame('College of Engineering', $profile->college);
        $this->assertSame('BS Computer Engineering', $profile->program);
        $this->assertSame('09171234567', $profile->contact_number);
        $this->assertSame(1.75, (float) $profile->gwa);
        $this->assertSame(150000.0, (float) $profile->family_income);
        $this->assertSame('123 Sampaguita St., Isabela', $profile->address);
        $this->assertSame('unknown', $profile->enrollment_status);

        Mail::assertSent(OtpVerificationMail::class, function (OtpVerificationMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && strlen($mail->otpCode) === 6
                && ctype_digit($mail->otpCode);
        });

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_cannot_assign_privileged_roles(): void
    {
        Mail::fake();

        $this->post('/register', array_merge([
            'student_number'   => '2026-1002',
            'name'             => 'Sneaky Student',
            'email'            => 'sneaky@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
            'role_id'          => Role::where('name', Role::ADMIN)->first()->id,
        ], $this->profileFields()));

        $user = User::where('email', 'sneaky@student.local')->first();
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_requires_valid_input(): void
    {
        $this->post('/register', [])->assertSessionHasErrors([
            'student_number', 'name', 'email', 'password', 'college', 'program',
            'contact_number', 'gwa', 'family_income', 'address', 'privacy_accepted',
        ]);
    }

    public function test_invalid_gwa_is_rejected(): void
    {
        $this->post('/register', array_merge([
            'student_number'   => '2026-1010',
            'name'             => 'Bad GWA',
            'email'            => 'badgwa@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields(['gwa' => '6.00'])))->assertSessionHasErrors('gwa');
    }

    public function test_negative_family_income_is_rejected(): void
    {
        $this->post('/register', array_merge([
            'student_number'   => '2026-1011',
            'name'             => 'Bad Income',
            'email'            => 'badincome@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields(['family_income' => '-1'])))->assertSessionHasErrors('family_income');
    }

    public function test_duplicate_student_number_is_rejected(): void
    {
        $existingUser = User::create([
            'name'     => 'Existing Student',
            'email'    => 'existing@student.local',
            'password' => 'secret-password',
            'role_id'  => $this->studentRoleId(),
        ]);

        StudentProfile::create([
            'user_id'        => $existingUser->id,
            'student_number' => '2026-1003',
            'first_name'     => 'Existing',
            'last_name'      => 'Student',
            'year_level'     => 1,
        ]);

        $this->post('/register', array_merge([
            'student_number'   => '2026-1003',
            'name'             => 'Another Student',
            'email'            => 'another@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields()))->assertSessionHasErrors('student_number');

        $this->assertDatabaseMissing('users', ['email' => 'another@student.local']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::create([
            'name'     => 'Taken Email',
            'email'    => 'taken@student.local',
            'password' => 'secret-password',
            'role_id'  => $this->studentRoleId(),
        ]);

        $this->post('/register', array_merge([
            'student_number'   => '2026-1004',
            'name'             => 'New Student',
            'email'            => 'taken@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields()))->assertSessionHasErrors('email');
    }

    public function test_privacy_acceptance_is_required(): void
    {
        $this->post('/register', array_merge([
            'student_number'   => '2026-1005',
            'name'             => 'No Privacy',
            'email'            => 'noprivacy@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
        ], $this->profileFields()))->assertSessionHasErrors('privacy_accepted');
    }

    public function test_short_password_is_rejected(): void
    {
        $this->post('/register', array_merge([
            'student_number'   => '2026-1006',
            'name'             => 'Short Pass',
            'email'            => 'shortpass@student.local',
            'password'         => 'short',
            'password_confirmation' => 'short',
            'privacy_accepted' => '1',
        ], $this->profileFields()))->assertSessionHasErrors('password');
    }

    public function test_registration_does_not_bypass_identity_or_enrollment_verification(): void
    {
        Mail::fake();

        $this->post('/register', array_merge([
            'student_number'   => '2026-1007',
            'name'             => 'Pending Student',
            'email'            => 'pending@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields()));

        $user = User::where('email', 'pending@student.local')->first();

        $this->assertNull($user->email_verified_at);

        $this->actingAs($user)
            ->get('/student/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_invalid_student_id_format_is_rejected(): void
    {
        $this->post('/register', array_merge([
            'student_number'   => 'bad id!!',
            'name'             => 'Bad ID',
            'email'            => 'badid@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'privacy_accepted' => '1',
        ], $this->profileFields()))->assertSessionHasErrors('student_number');
    }
}
