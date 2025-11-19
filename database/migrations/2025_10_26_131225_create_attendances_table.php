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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->decimal('overtime_minutes', 5, 2)->nullable();
            $table->decimal('late_minutes', 5, 2)->nullable();

            $table->json('location_in')->nullable();
            $table->json('location_out')->nullable();
            $table->boolean('fingerprint_verified')->default(false);
            $table->enum('status', ['present', 'absent', 'late', 'on_leave', 'remote'])->default('present');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
