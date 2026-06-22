<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal. Orquesta el orden de ejecución de los seeders del sistema.
 * Se ejecuta con: php artisan db:seed (o migrate:fresh --seed en desarrollo).
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders en el orden correcto respetando dependencias entre tablas.
     */
    public function run(): void
    {
        $this->call([
            CompetenciasUniversalesSeeder::class,
        ]);
    }
}
