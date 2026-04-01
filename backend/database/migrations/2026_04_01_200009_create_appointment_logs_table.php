<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();

            // Nullable: null = alteração feita pelo cliente via link público
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('old_status', ['scheduled', 'completed', 'cancelled', 'no_show'])->nullable();
            $table->enum('new_status', ['scheduled', 'completed', 'cancelled', 'no_show']);
            $table->timestamp('changed_at')->useCurrent();

            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_logs');
    }
};
