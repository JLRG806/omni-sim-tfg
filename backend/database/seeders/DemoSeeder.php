<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Siembra datos de demostración para el TFG.
 * Ejecutar con: php artisan db:seed --class=DemoSeeder
 * O con reset:  php artisan migrate:fresh --seed
 *
 * Crea un conjunto mínimo de datos reales para:
 * - Verificar el flujo completo en el browser
 * - Tener trazabilidad en la defensa
 * - Estabilizar el entorno local de desarrollo
 */
class DemoSeeder extends Seeder
{
    /**
     * Siembra el conjunto de demo: 1 admin, 2 profesores, 5 alumnos,
     * 3 asignaturas y matrículas cruzadas.
     */
    public function run(): void
    {
        // Desactivar foreign keys durante la siembra
        DB::statement('SET session_replication_role = replica');

        // ── Usuarios ────────────────────────────────────────────────────────

        $admin = User::updateOrCreate(
            ['email' => 'admin@omnisim.test'],
            ['name' => 'Admin OmniSim', 'password' => 'password', 'rol' => 'admin', 'estado' => 'activo', 'email_verified_at' => now()]
        );

        $garcia = User::updateOrCreate(
            ['email' => 'garcia@omnisim.test'],
            ['name' => 'Dr. Juan García López', 'password' => 'password', 'rol' => 'profesor', 'estado' => 'activo', 'email_verified_at' => now()]
        );

        $lopez = User::updateOrCreate(
            ['email' => 'lopez@omnisim.test'],
            ['name' => 'Dra. María López Ruiz', 'password' => 'password', 'rol' => 'profesor', 'estado' => 'activo', 'email_verified_at' => now()]
        );

        $alumnos = [
            User::updateOrCreate(['email' => 'ana@omnisim.test'],    ['name' => 'Ana Martínez García',  'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
            User::updateOrCreate(['email' => 'carlos@omnisim.test'], ['name' => 'Carlos Ruiz Pérez',    'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
            User::updateOrCreate(['email' => 'laura@omnisim.test'],  ['name' => 'Laura Sánchez Torres', 'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
            User::updateOrCreate(['email' => 'pedro@omnisim.test'],  ['name' => 'Pedro García Moreno',  'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
            User::updateOrCreate(['email' => 'elena@omnisim.test'],  ['name' => 'Elena Torres Navarro', 'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
        ];

        // ── Asignaturas ──────────────────────────────────────────────────────

        $psi = Asignatura::updateOrCreate(
            ['codigo' => 'PSI-301'],
            ['nombre' => 'Psicología Clínica', 'descripcion' => 'Técnicas de entrevista y evaluación psicológica en contexto clínico.', 'profesor_id' => $garcia->id]
        );

        $inf = Asignatura::updateOrCreate(
            ['codigo' => 'INF-401'],
            ['nombre' => 'Ingeniería de Requisitos', 'descripcion' => 'Elicitación y gestión de requisitos de software mediante técnicas de entrevista.', 'profesor_id' => $lopez->id]
        );

        $dit = Asignatura::updateOrCreate(
            ['codigo' => 'DIT-201'],
            ['nombre' => 'Dietética y Nutrición', 'descripcion' => 'Entrevista clínica nutricional y recogida de información alimentaria.', 'profesor_id' => $garcia->id]
        );

        // ── Matrículas ───────────────────────────────────────────────────────

        $matriculas = [
            [$alumnos[0]->id, $psi->id],  // Ana → Psicología
            [$alumnos[1]->id, $psi->id],  // Carlos → Psicología
            [$alumnos[2]->id, $psi->id],  // Laura → Psicología
            [$alumnos[1]->id, $inf->id],  // Carlos → Ingeniería
            [$alumnos[3]->id, $inf->id],  // Pedro → Ingeniería
            [$alumnos[4]->id, $inf->id],  // Elena → Ingeniería
            [$alumnos[2]->id, $dit->id],  // Laura → Dietética
            [$alumnos[4]->id, $dit->id],  // Elena → Dietética
        ];

        foreach ($matriculas as [$alumnoId, $asignaturaId]) {
            Matricula::updateOrCreate(
                ['alumno_id' => $alumnoId, 'asignatura_id' => $asignaturaId],
                ['fecha_matricula' => now()->toDateString()]
            );
        }

        DB::statement('SET session_replication_role = DEFAULT');

        $this->command->info('✅ DemoSeeder: 1 admin + 2 profesores + 5 alumnos + 3 asignaturas + 8 matrículas');
        $this->command->info('   Credenciales: *@omnisim.test / password');
        $this->command->table(
            ['Rol', 'Email', 'Nombre'],
            [
                ['admin',    'admin@omnisim.test',   'Admin OmniSim'],
                ['profesor', 'garcia@omnisim.test',  'Dr. Juan García López'],
                ['profesor', 'lopez@omnisim.test',   'Dra. María López Ruiz'],
                ['alumno',   'ana@omnisim.test',     'Ana Martínez García'],
                ['alumno',   'carlos@omnisim.test',  'Carlos Ruiz Pérez'],
                ['alumno',   'laura@omnisim.test',   'Laura Sánchez Torres'],
                ['alumno',   'pedro@omnisim.test',   'Pedro García Moreno'],
                ['alumno',   'elena@omnisim.test',   'Elena Torres Navarro'],
            ]
        );
    }
}
