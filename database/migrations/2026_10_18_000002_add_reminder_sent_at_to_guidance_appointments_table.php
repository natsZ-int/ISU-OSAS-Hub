<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotency guard for guidance appointment reminder emails so the
     * scheduled reminder command never notifies the same appointment twice.
     */
    public function up(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('guidance_appointments', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('guidance_appointments', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });
    }
};
