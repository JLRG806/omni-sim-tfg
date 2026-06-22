<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->nullable()->constrained('escenarios');
            $table->string('nombre');
            $table->text('descripcion');
            $table->enum('tipo', ['universal', 'personalizada'])->default('personalizada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencias');
    }
};
