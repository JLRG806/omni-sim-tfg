<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('asignaturas');
            $table->foreignId('profesor_id')->constrained('users');
            $table->string('titulo');
            $table->string('area_conocimiento');
            $table->text('descripcion_situacion');
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escenarios');
    }
};
