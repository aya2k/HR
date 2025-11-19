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
            $table->string('name')->default('Default Payroll Policy');
            $table->decimal('hour_rate', 10, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(1.25);   // multiplier
            $table->decimal('weekend_ot_rate', 10, 2)->default(1.5);
            $table->decimal('holiday_ot_rate', 10, 2)->default(2.0);
            $table->boolean('deduct_deficit')->default(true);
            $table->unsignedSmallInteger('round_to_minutes')->default(1);
            $table->json('components')->nullable(); 
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
