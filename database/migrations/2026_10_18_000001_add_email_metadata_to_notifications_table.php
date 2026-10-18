<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the metadata needed to render and track outbound notification emails.
     *
     * - data:       non-confidential context (reference, status, next step, URL, reason)
     * - emailed_at: idempotency guard so a retried job never emails twice
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('related_id');
            }

            if (! Schema::hasColumn('notifications', 'emailed_at')) {
                $table->timestamp('emailed_at')->nullable()->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'data')) {
                $table->dropColumn('data');
            }

            if (Schema::hasColumn('notifications', 'emailed_at')) {
                $table->dropColumn('emailed_at');
            }
        });
    }
};
