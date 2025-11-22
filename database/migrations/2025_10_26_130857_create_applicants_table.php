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
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('preferred_name')->nullable();
            $table->string('national_id')->unique();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('whatsapp_number')->nullable();
            $table->date('birth_date');

            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('governorate_id')->nullable()->constrained()->onDelete('set null');
            $table->string('city')->nullable();

            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('military_service', ['completed', 'exempted', 'postponed', 'not_required'])->nullable();
            $table->string('image')->nullable();

            $table->foreignId('position_applied_for_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employment_type_id')->nullable()->constrained()->onDelete('set null');

            // $table->string('employment_type')->nullable();  
            $table->enum('work_setup', ['onsite', 'remote', 'hybrid'])->nullable();
            $table->date('available_start_date')->nullable();
            $table->decimal('expected_salary', 10, 2)->nullable();
            $table->string('how_did_you_hear_about_this_role')->nullable();

            //Education
            $table->string('certification_attatchment')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('github_link')->nullable();
           $table->json('additional_link')->nullable();

            $table->string('cv')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('cover_letter')->nullable();
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
