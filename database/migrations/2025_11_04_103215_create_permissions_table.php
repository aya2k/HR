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
            $table->time('from_time');
            $table->time('to_time');
            $table->boolean('is_paid')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['annual', 'sick', 'casual', 'unpaid', 'other']);
            $table->date('from_date');
            $table->date('to_date');
            $table->boolean('is_paid')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('from_time');
            $table->time('to_time');
            $table->string('place')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();
        });
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_national')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(['permissions','leaves','missions','holidays']);
    }
};
