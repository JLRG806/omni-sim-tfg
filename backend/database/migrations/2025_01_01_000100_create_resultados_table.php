<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_simulacion_id')->unique()->constrained('sesiones_simulacion');
            $table->enum('estado', ['pendiente', 'procesando', 'evaluado'])->default('pendiente');
            $table->text('borrador_resumen')->nullable();
            $table->json('borrador_mapa_descubrimiento')->nullable();
            $table->json('borrador_competencias')->nullable();
            $table->decimal('borrador_calificacion', 4, 2)->nullable();
            $table->text('borrador_feedback')->nullable();
            $table->timestamp('borrador_generado_at')->nullable();
            $table->decimal('final_calificacion', 4, 2)->nullable();
            $table->text('final_feedback')->nullable();
            $table->json('final_competencias')->nullable();
            $table->timestamp('publicado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados');
    }
};
