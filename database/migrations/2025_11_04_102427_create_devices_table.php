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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('model')->nullable(); // ZKT ECO
            $table->string('ip')->nullable();
            $table->unsignedInteger('port')->default(4370);
            $table->string('serial')->nullable();
            $table->enum('mode', ['pull', 'push', 'zkbiotime'])->default('pull');
            $table->json('config')->nullable(); // مفاتيح/ADMS/…إلخ
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
