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
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('vendor')->nullable();      // e.g., ZKTeco
            $table->string('model')->nullable();
            $table->string('serial')->nullable()->unique();
            $table->string('ip')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('timezone')->default('Africa/Cairo');
            $table->enum('mode', ['pull', 'push'])->default('pull');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
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
