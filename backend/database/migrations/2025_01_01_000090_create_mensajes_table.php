<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_simulacion_id')->constrained('sesiones_simulacion');
            $table->enum('emisor', ['alumno', 'agente']);
            $table->text('contenido');
            $table->unsignedInteger('orden');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
