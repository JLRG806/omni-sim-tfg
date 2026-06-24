<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el campo tipo a sesiones_simulacion.
     * real    = sesión de un alumno (evaluable, aparece en historial).
     * prueba  = sesión de prueba del profesor (no evaluable, no aparece en historial).
     */
    public function up(): void
    {
        Schema::table('sesiones_simulacion', function (Blueprint $table) {
            $table->enum('tipo', ['real', 'prueba'])->default('real')->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesiones_simulacion', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
