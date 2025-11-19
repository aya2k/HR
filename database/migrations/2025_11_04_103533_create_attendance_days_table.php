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
            $table->date('work_date');                    // تاريخ اليوم (حسب branch TZ)
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            // من الشيفت/السياسة
            $table->unsignedSmallInteger('required_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);    // ثابته أو من السياسة

            // من واقع البصمات المحسوبة
            $table->timestamp('first_in_at')->nullable();   // UTC
            $table->timestamp('last_out_at')->nullable();   // UTC
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('deficit_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedSmallInteger('punches_count')->default(0);

            // الحالة النهائية
            $table->enum('day_type', ['workday', 'weekend', 'holiday', 'leave', 'permission', 'absent'])->default('workday');
            $table->enum('status', ['complete', 'partial', 'absent', 'excused'])->default('partial');

            $table->json('components')->nullable(); // تفصيل الخصومات/الزيادات
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['branch_id', 'work_date']);
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
