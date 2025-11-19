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
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->time('start_time')->nullable();   // local branch time
            $table->time('end_time')->nullable();     // local branch time
            $table->unsignedSmallInteger('required_minutes')->default(480); // 8 ساعات
            $table->unsignedSmallInteger('break_minutes')->default(60);
            $table->boolean('flexible')->default(true);
            $table->unsignedSmallInteger('flex_before')->nullable();  // مسموح ييجي قبل/بعد
            $table->unsignedSmallInteger('flex_after')->nullable(); 
            $table->boolean('overnight')->default(false); // cross-day
            $table->json('meta')->nullable();

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
        Schema::dropIfExists('employee_schedules');
    }
};
