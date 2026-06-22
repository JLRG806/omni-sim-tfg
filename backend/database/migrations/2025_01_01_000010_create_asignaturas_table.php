<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')->constrained('users');
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaturas');
    }
};
