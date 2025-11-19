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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->unsignedSmallInteger('minutes')->default(0);
            $table->string('reason')->nullable();

            $table->enum('type', ['personal', 'official', 'mission'])->default('personal');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained(table: 'users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(['permissions']);
    }
};
