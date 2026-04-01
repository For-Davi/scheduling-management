<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Nullable: null = bloqueio da empresa inteira; preenchido = bloqueio pessoal do funcionário
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();

            $table->date('date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_days');
    }
};
