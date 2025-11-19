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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->enum('leave_type', ['annual', 'sick', 'unpaid', 'casual', 'maternity', 'other']);
            $table->enum('unit', ['day', 'hour'])->default('day');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // لليوم الواحد = نفس اليوم
            $table->unsignedSmallInteger('minutes')->default(0); // لو unit=hour
            $table->boolean('is_half_day')->default(false);

            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained(table: 'users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
