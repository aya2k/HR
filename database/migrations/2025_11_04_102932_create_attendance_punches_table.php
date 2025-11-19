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
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('punched_at'); // UTC
            $table->enum('direction', ['in', 'out'])->nullable(); // بعد التحليل
            $table->enum('source', ['device', 'manual', 'api'])->default('device');
            $table->unsignedSmallInteger('confidence')->nullable(); // 0-100
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('dedup_hash')->unique();  // منع التكرار
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'punched_at']);
            $table->index(['branch_id', 'punched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');
    }
};
