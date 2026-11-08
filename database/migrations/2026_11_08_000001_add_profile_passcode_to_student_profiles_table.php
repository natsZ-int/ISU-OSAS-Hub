<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            // Stored as a one-way hash. The plain-text passcode is never
            // persisted or rendered by the application.
            $table->string('profile_passcode_hash')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('profile_passcode_hash');
        });
    }
};
