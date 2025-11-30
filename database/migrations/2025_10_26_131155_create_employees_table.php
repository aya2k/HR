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
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->references('id')->on('employees')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();

            // Employment
            $table->json('hybrid_schedule')->nullable();
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('contract_duration')->nullable();
            $table->enum('status', ['accepted', 'rejected', 'pending'])->default('pending');

            $table->foreignId('managed_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('managed_branch_id')->nullable()->constrained('branches')->nullOnDelete();

            // Attendance
            $table->boolean('has_fingerprint')->default(true);
            $table->boolean('has_location_tracking')->default(false);
            $table->json('weekly_work_days')->nullable();
            $table->integer('monthly_hours_required')->nullable();

            // Salary & Compensation
            $table->enum('compensation_type', ['fixed', 'hourly', 'commission', 'mixed'])->default('fixed');
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('commission_percentage', 8, 2)->default(0);
            $table->decimal('kpi', 8, 2)->default(0);
            $table->string('salary_method')->nullable();
            $table->boolean('has_fixed_salary')->default(true);
            $table->integer('num_of_call_system')->default(0);
            $table->boolean('is_manager')->default(false);
            $table->boolean('is_department_manager')->default(false);
            $table->boolean('is_branch_manager')->default(false);
            $table->boolean('manager_for_all_branches')->default(false);
            $table->string('contract_type')->nullable();
            $table->boolean('is_sales')->default(false);
            $table->enum('salary_type', ['single', 'multi'])->default('single');
            $table->json('salary_details')->nullable();
            $table->json('contracts')->nullable();

             $table->string('card_number')->nullable();
              $table->string('wallet_number')->nullable();

            $table->enum('part_time_type', ['hours', 'days'])->nullable();  // ساعات ولا أيام
            $table->integer('total_hours')->nullable();

            $table->timestamps();
        });

        Schema::create('branch_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('branch_employee');
    }
};
