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
        Schema::create('payroll_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('compensation_type', ['fixed', 'hourly', 'commission', 'mixed'])->default('fixed');
            $table->decimal('overtime_rate_multiplier', 4, 2)->default(1.25); // 125% مثلاً
            $table->decimal('holiday_work_multiplier', 4, 2)->default(2.00);
            $table->enum('deduction_mode', ['per_minute', 'tiers'])->default('per_minute');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_policies');
    }
};
