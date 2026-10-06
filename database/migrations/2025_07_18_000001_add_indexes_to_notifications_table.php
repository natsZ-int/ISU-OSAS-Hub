<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for efficient unread-count queries per user
            $table->index(['user_id', 'read_at'], 'idx_notif_user_read');
            // Index for ordering by creation time per user
            $table->index(['user_id', 'created_at'], 'idx_notif_user_created');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_user_read');
            $table->dropIndex('idx_notif_user_created');
        });
    }
};