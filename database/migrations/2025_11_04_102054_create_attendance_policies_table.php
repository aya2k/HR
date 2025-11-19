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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default Global Policy');
            $table->unsignedSmallInteger('default_required')->default(480);
            $table->unsignedSmallInteger('default_break')->default(60);
            $table->unsignedSmallInteger('late_grace')->default(15);
            $table->unsignedSmallInteger('early_grace')->nullable();
            $table->unsignedSmallInteger('max_daily_deficit_compensate')->nullable();
            $table->json('overtime_rules')->nullable();
            $table->json('penalties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
