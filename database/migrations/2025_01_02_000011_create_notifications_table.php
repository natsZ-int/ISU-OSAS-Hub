<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title', 255);
            $table->text('message');
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('read_at');
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
