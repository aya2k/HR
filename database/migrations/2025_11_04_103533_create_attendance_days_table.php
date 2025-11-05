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
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('work_minutes_raw')->default(0);
            $table->unsignedInteger('permission_minutes_unpaid')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('late_minutes_weighted')->default(0); // بعد المضاعف
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->boolean('is_holiday')->default(false);
            $table->enum('state', ['ok', 'incomplete', 'absent', 'leave', 'mission'])->default('ok');
            $table->json('notes')->nullable();
            $table->unique(['employee_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
