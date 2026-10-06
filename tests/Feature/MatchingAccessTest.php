<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MatchingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        ScholarshipMatchingWeight::create([
            'academic_weight' => 30, 'financial_weight' => 25, 'program_weight' => 20,
            'year_level_weight' => 15, 'document_readiness_weight' => 10, 'is_active' => true,
        ]);
    }

    private function admin(): User { return User::create(['name'=>'A','email'=>'a@t.l','password'=>Hash::make('p'),'role_id'=>Role::where('name','admin')->first()->id,'is_active'=>true]); }
    private function studentUser(): User {
        $u = User::create(['name'=>'S','email'=>'s@t.l','password'=>Hash::make('p'),'role_id'=>Role::where('name','student')->first()->id,'is_active'=>true]);
        StudentProfile::create(['user_id'=>$u->id,'student_number'=>'S1','first_name'=>'S','last_name'=>'T','year_level'=>3,'gwa'=>1.50]);
        return $u;
    }
    private function counselor(): User { return User::create(['name'=>'C','email'=>'c@t.l','password'=>Hash::make('p'),'role_id'=>Role::where('name','counselor')->first()->id,'is_active'=>true]); }

    public function test_guest_cannot_view_recommendations(): void { $this->get('/student/recommended-scholarships')->assertRedirect('/login'); }

    public function test_student_can_view_recommendations(): void {
        $this->actingAs($this->studentUser());
        $this->get('/student/recommended-scholarships')->assertOk();
    }

    public function test_student_cannot_access_weights(): void {
        $this->actingAs($this->studentUser());
        $this->get('/admin/scholarship-matching/weights')->assertStatus(403);
    }

    public function test_counselor_cannot_access_weights(): void {
        $this->actingAs($this->counselor());
        $this->get('/admin/scholarship-matching/weights')->assertStatus(403);
    }

    public function test_admin_can_access_weights(): void {
        $this->actingAs($this->admin());
        $this->get('/admin/scholarship-matching/weights')->assertOk();
    }

    public function test_admin_can_save_valid_weights(): void {
        $this->actingAs($this->admin());
        $this->put('/admin/scholarship-matching/weights', [
            'academic_weight' => 40, 'financial_weight' => 20, 'program_weight' => 20,
            'year_level_weight' => 10, 'document_readiness_weight' => 10,
        ])->assertSessionHas('success');
    }

    public function test_admin_cannot_save_invalid_total(): void {
        $this->actingAs($this->admin());
        $this->put('/admin/scholarship-matching/weights', [
            'academic_weight' => 50, 'financial_weight' => 50, 'program_weight' => 50,
            'year_level_weight' => 50, 'document_readiness_weight' => 50,
        ])->assertSessionHasErrors('total');
    }

    public function test_negative_weights_rejected(): void {
        $this->actingAs($this->admin());
        $this->put('/admin/scholarship-matching/weights', [
            'academic_weight' => -10, 'financial_weight' => 40, 'program_weight' => 30,
            'year_level_weight' => 20, 'document_readiness_weight' => 20,
        ])->assertSessionHasErrors('academic_weight');
    }
}