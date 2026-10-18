<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpVerificationMail;
use App\Models\Role;
use App\Models\StudentEnrollmentVerification;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\OtpVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function otp(): OtpVerificationService
    {
        return app(OtpVerificationService::class);
    }

    private function createUnverifiedStudent(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name'     => 'OTP Student',
            'email'    => 'otp@student.local',
            'password' => 'secret-password',
            'role_id'  => Role::where('name', Role::STUDENT)->first()->id,
            'is_active' => true,
        ], $overrides));

        StudentProfile::create([
            'user_id'        => $user->id,
            'student_number' => '2026-2001',
            'first_name'     => 'OTP',
            'last_name'      => 'Student',
            'year_level'     => 1,
        ]);

        return $user;
    }

    public function test_verification_page_requires_authentication(): void
    {
        $this->get('/email/verify')->assertRedirect('/login');
    }

    public function test_verification_page_is_shown_to_unverified_user(): void
    {
        $user = $this->createUnverifiedStudent();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('Verify Your Email');
    }

    public function test_verified_user_is_redirected_away_from_verification_page(): void
    {
        $user = $this->createUnverifiedStudent();
        $user->markEmailAsVerified();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_correct_code_verifies_email_and_creates_pending_enrollment_verification(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertRedirect(route('student.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->assertDatabaseHas('student_enrollment_verifications', [
            'student_profile_id' => $user->studentProfile->id,
            'academic_year'      => config('academic.current_year'),
            'semester'           => config('academic.current_semester'),
            'status'             => 'pending',
        ]);
    }

    public function test_incorrect_code_is_rejected(): void
    {
        $user = $this->createUnverifiedStudent();
        $this->otp()->issue($user);

        $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_non_six_digit_code_is_rejected(): void
    {
        $user = $this->createUnverifiedStudent();
        $this->otp()->issue($user);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => '12345'])
            ->assertSessionHasErrors('code');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        $this->travel(OtpVerificationService::EXPIRY_MINUTES + 1)->minutes();

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_maximum_attempt_limit_is_enforced(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        for ($i = 0; $i < OtpVerificationService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($user)->post('/email/verify', ['code' => '999999']);
        }

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_consumed_code_cannot_be_reused(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        $this->actingAs($user)->post('/email/verify', ['code' => $code]);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_resend_invalidates_the_previous_code(): void
    {
        $user  = $this->createUnverifiedStudent();
        $code1 = $this->otp()->issue($user);

        $this->travel(OtpVerificationService::RESEND_COOLDOWN + 1)->seconds();

        $code2 = $this->otp()->issue($user);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code1])
            ->assertSessionHasErrors('code');

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code2])
            ->assertRedirect(route('student.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resend_endpoint_sends_new_code_and_respects_cooldown(): void
    {
        Mail::fake();

        $user = $this->createUnverifiedStudent();
        $this->otp()->issue($user);

        $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/resend')
            ->assertSessionHasErrors('resend');

        $this->travel(OtpVerificationService::RESEND_COOLDOWN + 1)->seconds();

        $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/resend')
            ->assertRedirect('/email/verify');

        Mail::assertSent(OtpVerificationMail::class, 1);
    }

    public function test_otp_is_only_stored_as_a_hash(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        $record = $user->otpVerifications()->latest('id')->first();

        $this->assertNotNull($record);
        $this->assertNotEquals($code, $record->code);
        $this->assertTrue(password_verify($code, $record->code));
    }

    public function test_registration_sends_initial_otp_without_manual_send(): void
    {
        Mail::fake();

        $this->post('/register', [
            'student_number'   => '2026-2002',
            'name'             => 'Auto OTP Student',
            'email'            => 'autootp@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'college'          => 'College of Engineering',
            'program'          => 'BS Computer Engineering',
            'contact_number'   => '09171234567',
            'gwa'              => '1.75',
            'family_income'    => '150000',
            'address'          => '123 Sampaguita St., Isabela',
            'privacy_accepted' => '1',
        ]);

        Mail::assertSent(OtpVerificationMail::class, 1);
    }

    public function test_mail_failure_is_handled_without_falsely_reporting_success(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

        $response = $this->post('/register', [
            'student_number'   => '2026-2003',
            'name'             => 'Mail Fail Student',
            'email'            => 'mailfail@student.local',
            'password'         => 'secret-password',
            'password_confirmation' => 'secret-password',
            'college'          => 'College of Engineering',
            'program'          => 'BS Computer Engineering',
            'contact_number'   => '09171234567',
            'gwa'              => '1.75',
            'family_income'    => '150000',
            'address'          => '123 Sampaguita St., Isabela',
            'privacy_accepted' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertNotNull(session('error'));

        $user = User::where('email', 'mailfail@student.local')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_email_blocks_student_routes(): void
    {
        $user = $this->createUnverifiedStudent();

        $this->actingAs($user)
            ->get('/student/dashboard')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->post('/student/service-requests')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_email_does_not_automatically_grant_enrollment_verification(): void
    {
        $user = $this->createUnverifiedStudent();
        $code = $this->otp()->issue($user);

        $this->actingAs($user)->post('/email/verify', ['code' => $code]);

        $user = $user->fresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEnrollmentForCurrentTerm());

        $this->actingAs($user)
            ->from('/student/dashboard')
            ->post('/student/service-requests')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_another_user_cannot_verify_someone_elses_account(): void
    {
        $victim  = $this->createUnverifiedStudent();
        $victimCode = $this->otp()->issue($victim);

        $attacker = User::create([
            'name'     => 'Attacker',
            'email'    => 'attacker@student.local',
            'password' => 'secret-password',
            'role_id'  => Role::where('name', Role::STUDENT)->first()->id,
            'is_active' => true,
        ]);
        $attacker->markEmailAsVerified();

        $this->actingAs($attacker)
            ->post('/email/verify', ['code' => $victimCode])
            ->assertRedirect(route('student.dashboard'));

        $this->assertNull($victim->fresh()->email_verified_at);
    }
}
