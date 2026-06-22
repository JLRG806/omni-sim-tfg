<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_agente_id')->constrained('perfiles_agente');
            $table->foreignId('competencia_id')->constrained('competencias');
            $table->text('contenido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_evaluacion');
    }
};
