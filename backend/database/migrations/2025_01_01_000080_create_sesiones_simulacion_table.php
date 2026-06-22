<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_simulacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('users');
            $table->foreignId('escenario_id')->constrained('escenarios');
            $table->enum('estado', ['en_curso', 'pausada', 'procesando', 'finalizada', 'evaluada'])->default('en_curso');
            $table->timestamp('inicio_at');
            $table->timestamp('finalizacion_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_simulacion');
    }
};
