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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();

            $table->string('job_title');
            $table->string('company_name');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship', 'freelance'])->nullable();
            $table->enum('work_setup', ['onsite', 'remote', 'hybrid'])->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('manager_name')->nullable();
            $table->string('manager_phone')->nullable();
            $table->string('manager_email')->nullable();
            $table->boolean('okay_to_contact')->default(false);
            $table->text('key_responsibilities')->nullable();      
             $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
