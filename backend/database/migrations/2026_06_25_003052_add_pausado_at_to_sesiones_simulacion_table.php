<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade pausado_at a sesiones_simulacion.
     * Se registra cuando el alumno pausa explícitamente (CU-31).
     * Permite calcular el tiempo activo real: finalizacion_at - inicio_at - tiempo_pausa.
     * Si el alumno cierra la pestaña sin pausar, pausado_at queda null
     * y el tiempo sigue corriendo hasta que reanude manualmente.
     */
    public function up(): void
    {
        Schema::table('sesiones_simulacion', function (Blueprint $table) {
            $table->timestamp('pausado_at')->nullable()->after('finalizacion_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesiones_simulacion', function (Blueprint $table) {
            $table->dropColumn('pausado_at');
        });
    }
};
