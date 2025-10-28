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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
             $table->foreignId('applicant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->references('id')->on('employees')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();

            // Employment
            $table->enum('employment_type', ['full_time','part_time','contract','internship'])->default('full_time');
            $table->enum('work_mode', ['on_site','remote','hybrid'])->default('on_site');
            $table->json('hybrid_schedule')->nullable();
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('contract_duration')->nullable();
            $table->enum('status', ['active','inactive','terminated','resigned'])->default('active');

            // Attendance
            $table->boolean('has_fingerprint')->default(true);
            $table->boolean('has_location_tracking')->default(false);
            $table->json('weekly_work_days')->nullable();
            $table->integer('monthly_hours_required')->nullable();

            // Salary & Compensation
            $table->enum('compensation_type', ['fixed','hourly','commission','mixed'])->default('fixed');
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('kpi', 5, 2)->default(0);
            $table->enum('salary_method', ['cash','bank','wallet'])->default('cash');
            $table->boolean('has_fixed_salary')->default(true);

            // Work details
            $table->integer('permission_hours')->default(0);
            $table->integer('work_hours')->default(8);
            $table->integer('holiday_balance')->default(0);
            $table->boolean('friday_off')->default(false);
            $table->json('holidays')->nullable();
            $table->boolean('uniform')->default(false);
            $table->string('expected_work_period')->nullable();
            $table->boolean('has_laptop')->default(false);
            $table->text('overtime_reason')->nullable();

            // Social links
            // $table->string('facebook_link')->nullable();
            // $table->string('linkedin_link')->nullable();

            // Tracking
            $table->integer('num_of_call_system')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
