<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_agente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->unique()->constrained('escenarios');
            $table->text('rol_identidad');
            $table->text('trasfondo');
            $table->text('conocimientos');
            $table->text('mensaje_bienvenida');
            $table->text('comportamiento');
            $table->enum('tono_emocional', ['formal', 'amigable', 'empatico', 'serio', 'distante']);
            $table->enum('nivel_dificultad', ['facil', 'medio', 'dificil'])->default('medio');
            $table->string('avatar_path')->nullable();
            $table->json('informacion_explicita');
            $table->json('informacion_latente');
            $table->json('restricciones');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_agente');
    }
};
