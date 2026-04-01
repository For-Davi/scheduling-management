<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Dados do cliente (sem login)
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone', 20)->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');

            // Consentimento LGPD aceito pelo cliente no momento do agendamento
            $table->boolean('lgpd_consent')->default(false);

            $table->timestamps();

            // Índices para consultas de disponibilidade e relatórios
            $table->index(['employee_id', 'starts_at', 'status']);
            $table->index(['company_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
