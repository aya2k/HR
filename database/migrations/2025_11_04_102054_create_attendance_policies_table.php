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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('standard_daily_hours')->default(8);
            $table->unsignedSmallInteger('grace_minutes_late')->default(15);
            $table->enum('late_rule', ['flat', 'double_rate_after_grace'])->default('double_rate_after_grace');
            $table->decimal('late_multiplier_after_grace', 4, 2)->default(2.00);
            $table->unsignedSmallInteger('pairing_max_session_hours')->default(12);
            $table->boolean('overtime_before_shift')->default(false);
            $table->boolean('overtime_after_shift')->default(true);
            $table->time('night_cutoff')->default('05:00:00');
            $table->unsignedSmallInteger('break_minutes_paid')->default(0);
            $table->unsignedSmallInteger('permission_monthly_quota_minutes')->default(240); // 4 ساعات
            $table->unsignedSmallInteger('leave_eligibility_after_months')->default(3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
