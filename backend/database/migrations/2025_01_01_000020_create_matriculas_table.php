<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('users');
            $table->foreignId('asignatura_id')->constrained('asignaturas');
            $table->date('fecha_matricula');
            $table->timestamps();
            $table->unique(['alumno_id', 'asignatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
