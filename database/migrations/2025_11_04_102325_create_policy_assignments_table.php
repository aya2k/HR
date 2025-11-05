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
        Schema::create('policy_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable'); // company/branch/department/position/employee
            $table->foreignId('attendance_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('priority')->default(100); // الأصغر = أعلى
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_assignments');
    }
};
