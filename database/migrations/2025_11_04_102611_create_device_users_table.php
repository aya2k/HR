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
        Schema::create('device_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('device_user_code'); // Code/UID داخل الجهاز
            $table->string('badge_no')->nullable();
            $table->string('privilege')->nullable();   // normal/admin
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'device_user_code']);
            $table->index(['employee_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_users');
    }
};
