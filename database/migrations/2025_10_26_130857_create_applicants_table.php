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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->string('last_name');
            $table->string('national_id')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->default(0);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('military_service', ['completed', 'exempted', 'postponed', 'not_required'])->nullable(); 
            $table->integer('num_of_children')->default(0);
            $table->string('image')->nullable();
            $table->foreignId('governorate_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->string('address_details')->nullable();
            $table->string('cv')->nullable();
            $table->decimal('expected_salary', 10, 2)->nullable();
            $table->date('available_date')->nullable();
            $table->json('skills')->nullable();
            $table->string('faculty')->nullable();
            $table->string('university')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->year('start_year')->nullable();
            $table->year('graduation_year')->nullable();
            $table->boolean('is_graduated')->default(true);
            $table->json('courses')->nullable();
            $table->json('previous_jobs')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('github_link')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
