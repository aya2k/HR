<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('enroll_id');
            $table->timestamp('verified_at');
            $table->enum('io', ['IN', 'OUT'])->nullable();
            $table->string('method')->nullable(); // Finger/Face/Card
            $table->string('sn')->nullable();
            $table->json('payload')->nullable();
            $table->unique(['device_id', 'enroll_id', 'verified_at']);
            $table->index(['verified_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_raw_logs');
    }
};
