<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pre-existing accounts were created by administrators, not through
     * self-registration, so they are treated as email-verified. This
     * backfill prevents the new email-verification middleware from
     * locking out existing admins, counselors, and students.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    /**
     * Data backfill is intentionally irreversible: nulling email_verified_at
     * for legacy accounts would re-trigger verification for users who never
     * registered themselves and cannot complete an OTP flow.
     */
    public function down(): void
    {
        //
    }
};
