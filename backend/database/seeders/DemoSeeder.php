<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\Competencia;
use App\Models\CriterioEvaluacion;
use App\Models\Escenario;
use App\Models\Matricula;
use App\Models\Mensaje;
use App\Models\ObjetivoAprendizaje;
use App\Models\PerfilAgente;
use App\Models\Resultado;
use App\Models\SesionSimulacion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Siembra datos de demostración completos para el TFG.
 * Ejecutar con: php artisan db:seed --class=DemoSeeder
 * O con reset:  php artisan migrate:fresh --seed
 *
 * Cubre TODOS los modelos para que cualquier /verifier-omnisim funcione sin
 * crear datos manualmente. Incluye el flujo completo de simulación.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ── Usuarios ─────────────────────────────────────────────────────

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

            [$ana, $carlos, $laura, $pedro, $elena] = [
                User::updateOrCreate(['email' => 'ana@omnisim.test'],    ['name' => 'Ana Martínez García',  'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
                User::updateOrCreate(['email' => 'carlos@omnisim.test'], ['name' => 'Carlos Ruiz Pérez',    'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
                User::updateOrCreate(['email' => 'laura@omnisim.test'],  ['name' => 'Laura Sánchez Torres', 'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
                User::updateOrCreate(['email' => 'pedro@omnisim.test'],  ['name' => 'Pedro García Moreno',  'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
                User::updateOrCreate(['email' => 'elena@omnisim.test'],  ['name' => 'Elena Torres Navarro', 'password' => 'password', 'rol' => 'alumno', 'estado' => 'activo', 'email_verified_at' => now()]),
            ];

            // ── Asignaturas ──────────────────────────────────────────────────

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

            // ── Matrículas ───────────────────────────────────────────────────

            foreach ([
                [$ana->id,    $psi->id],
                [$carlos->id, $psi->id],
                [$laura->id,  $psi->id],
                [$carlos->id, $inf->id],
                [$pedro->id,  $inf->id],
                [$elena->id,  $inf->id],
                [$laura->id,  $dit->id],
                [$elena->id,  $dit->id],
            ] as [$alumnoId, $asigId]) {
                Matricula::updateOrCreate(
                    ['alumno_id' => $alumnoId, 'asignatura_id' => $asigId],
                    ['fecha_matricula' => now()->toDateString()]
                );
            }

            // ── Competencias universales (por si no las sembró el seeder previo) ──

            $compTecnica = Competencia::firstOrCreate(
                ['nombre' => 'Técnica de preguntas', 'escenario_id' => null],
                ['descripcion' => 'Capacidad para formular preguntas abiertas, cerradas y de seguimiento de forma apropiada.', 'tipo' => 'universal']
            );
            $compCobertura = Competencia::firstOrCreate(
                ['nombre' => 'Cobertura', 'escenario_id' => null],
                ['descripcion' => 'Amplitud de la información recogida durante la entrevista.', 'tipo' => 'universal']
            );

            // ── Escenario PSI-301 (publicado) ────────────────────────────────

            $escPsi = Escenario::updateOrCreate(
                ['asignatura_id' => $psi->id, 'titulo' => 'Entrevista con paciente ansioso'],
                [
                    'profesor_id'           => $garcia->id,
                    'area_conocimiento'     => 'Psicología Clínica',
                    'descripcion_situacion' => 'El paciente llega a la consulta con signos visibles de ansiedad. Ha sido derivado por su médico de cabecera. El alumno debe realizar una entrevista inicial para recoger información sobre sus síntomas y su historia personal.',
                    'estado'                => 'publicado',
                ]
            );

            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'orden' => 1],
                ['contenido' => 'Identificar los síntomas de ansiedad mediante preguntas directas e indirectas']
            );
            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'orden' => 2],
                ['contenido' => 'Aplicar técnicas de escucha activa durante la entrevista']
            );
            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'orden' => 3],
                ['contenido' => 'Detectar información latente sobre el origen del problema']
            );

            $perfilPsi = PerfilAgente::updateOrCreate(
                ['escenario_id' => $escPsi->id],
                [
                    'rol_identidad'        => 'Paciente adulto de 38 años con trastorno de ansiedad generalizada diagnosticado hace 6 meses',
                    'trasfondo'            => 'Trabaja como contable en una empresa mediana. Está casado con dos hijos. El estrés laboral aumentó considerablemente tras un cambio de dirección en la empresa.',
                    'conocimientos'        => 'Sabe que tiene ansiedad pero no comprende bien los mecanismos ni los tratamientos disponibles. Desconoce que el origen puede ser laboral.',
                    'mensaje_bienvenida'   => 'Buenos días. Me ha mandado el médico... aunque no sé muy bien para qué sirve esto.',
                    'comportamiento'       => 'Habla con rapidez cuando está nervioso, tiende a desviar los temas cuando se acerca a algo que le incomoda, usa el humor defensivo.',
                    'tono_emocional'       => 'serio',
                    'nivel_dificultad'     => 'medio',
                    'informacion_explicita' => [
                        'Sufre ansiedad diagnosticada hace 6 meses',
                        'Tiene insomnio frecuente',
                        'Toma medicación (alprazolam 0.5mg)',
                    ],
                    'informacion_latente'  => [
                        'Conflicto serio con su nuevo jefe que no quiere revelar directamente',
                        'Siente que puede perder su trabajo pero no lo admite fácilmente',
                        'Su mujer le ha sugerido que vaya al psicólogo pero él lo veía como debilidad',
                    ],
                    'restricciones'        => [
                        'Compórtate de forma natural y profesional.',
                        'Solo revela información latente ante buenas preguntas de seguimiento.',
                        'Sé selectivo con lo que compartes espontáneamente.',
                    ],
                ]
            );

            CriterioEvaluacion::updateOrCreate(
                ['perfil_agente_id' => $perfilPsi->id, 'competencia_id' => $compTecnica->id],
                ['contenido' => 'Usa preguntas abiertas para explorar el estado emocional del paciente']
            );
            CriterioEvaluacion::updateOrCreate(
                ['perfil_agente_id' => $perfilPsi->id, 'competencia_id' => $compCobertura->id],
                ['contenido' => 'Cubre los ámbitos: síntomas, historia, contexto familiar y laboral']
            );

            // ── Escenario INF-401 (publicado) ────────────────────────────────

            $escInf = Escenario::updateOrCreate(
                ['asignatura_id' => $inf->id, 'titulo' => 'Entrevista con stakeholder de sistema de gestión'],
                [
                    'profesor_id'           => $lopez->id,
                    'area_conocimiento'     => 'Ingeniería de Requisitos',
                    'descripcion_situacion' => 'El stakeholder es el director financiero de una empresa que quiere digitalizar su proceso de aprobación de facturas. El alumno debe elicitar los requisitos del nuevo sistema.',
                    'estado'                => 'publicado',
                ]
            );

            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escInf->id, 'orden' => 1],
                ['contenido' => 'Identificar los requisitos funcionales del sistema mediante técnicas de elicitación']
            );
            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escInf->id, 'orden' => 2],
                ['contenido' => 'Detectar restricciones presupuestarias y temporales del proyecto']
            );

            $perfilInf = PerfilAgente::updateOrCreate(
                ['escenario_id' => $escInf->id],
                [
                    'rol_identidad'        => 'Director Financiero de empresa mediana (120 empleados), 52 años, muy ocupado',
                    'trasfondo'            => 'Lleva 15 años en la empresa. Es escéptico con la tecnología pero reconoce que el proceso actual es ineficiente. Tiene presupuesto limitado.',
                    'conocimientos'        => 'Conoce bien el proceso de negocio actual pero no entiende de tecnología. No sabe qué es posible ni cuánto cuesta.',
                    'mensaje_bienvenida'   => 'Tiene diez minutos, ¿verdad? Es lo máximo que puedo darle.',
                    'comportamiento'       => 'Directo al grano, impaciente, usa jerga financiera, pregunta por el coste antes que por la funcionalidad.',
                    'tono_emocional'       => 'formal',
                    'nivel_dificultad'     => 'dificil',
                    'informacion_explicita' => [
                        'El proceso actual tarda 3 semanas en aprobar una factura',
                        'Hay 3 aprobadores por factura',
                        'El presupuesto máximo es 50.000€',
                    ],
                    'informacion_latente'  => [
                        'Ha habido un fraude interno el año pasado por facturas aprobadas sin control',
                        'El CEO le ha dado un ultimátum de 6 meses para digitalizar el proceso',
                        'Tiene miedo de que el sistema revele su propia ineficiencia',
                    ],
                    'restricciones'        => [
                        'Sé evasivo y da respuestas vagas cuando sea posible.',
                        'Revela información latente únicamente ante preguntas muy específicas y directas.',
                        'Crea cierta resistencia natural al compartir información sensible.',
                    ],
                ]
            );

            CriterioEvaluacion::updateOrCreate(
                ['perfil_agente_id' => $perfilInf->id, 'competencia_id' => $compTecnica->id],
                ['contenido' => 'Usa preguntas de embudo para extraer requisitos específicos']
            );

            // ── Escenario DIT-201 (publicado) ────────────────────────────────────

            $escDit = Escenario::updateOrCreate(
                ['asignatura_id' => $dit->id, 'titulo' => 'Consulta nutricional inicial'],
                [
                    'profesor_id'           => $garcia->id,
                    'area_conocimiento'     => 'Dietética y Nutrición',
                    'descripcion_situacion' => 'Paciente que acude a consulta de nutrición por primera vez. Quiere perder peso pero tiene hábitos alimenticios muy arraigados. El alumno debe recoger la historia dietética completa.',
                    'estado'                => 'publicado',
                ]
            );

            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escDit->id, 'orden' => 1],
                ['contenido' => 'Recoger la historia dietética completa del paciente']
            );
            ObjetivoAprendizaje::updateOrCreate(
                ['escenario_id' => $escDit->id, 'orden' => 2],
                ['contenido' => 'Identificar hábitos problemáticos sin generar rechazo']
            );

            $perfilDit = PerfilAgente::updateOrCreate(
                ['escenario_id' => $escDit->id],
                [
                    'rol_identidad'        => 'Mujer de 45 años, auxiliar administrativa, con sobrepeso moderado (IMC 28)',
                    'trasfondo'            => 'Cocina tradicional, cena tarde por trabajo. Familia numerosa, difícil cambiar hábitos. Intentó varias dietas sin éxito.',
                    'conocimientos'        => 'Sabe que come mal pero no identifica exactamente dónde falla. Cree que el problema es el pan y los dulces.',
                    'mensaje_bienvenida'   => 'Hola, vengo porque mi médico me ha dicho que tengo que bajar algo de peso... aunque ya sé que como bastante mal.',
                    'comportamiento'       => 'Habla mucho de su familia, justifica sus hábitos, se pone defensiva cuando se cuestiona su cocina tradicional.',
                    'tono_emocional'       => 'amigable',
                    'nivel_dificultad'     => 'facil',
                    'informacion_explicita' => [
                        'IMC 28 - sobrepeso moderado',
                        'Cena entre las 22:00 y las 23:00',
                        'Come fruta todos los días',
                    ],
                    'informacion_latente'  => [
                        'Pica entre horas especialmente bollería industrial',
                        'Bebe 2-3 vasos de vino con la cena los fines de semana',
                        'No hace ningún ejercicio físico aunque dice que "camina mucho"',
                    ],
                    'restricciones'        => [
                        'Sé cooperativo y comparte información con facilidad.',
                        'Revela información latente con preguntas básicas.',
                        'Muéstrate dispuesto a colaborar con el entrevistador.',
                    ],
                ]
            );

            CriterioEvaluacion::updateOrCreate(
                ['perfil_agente_id' => $perfilDit->id, 'competencia_id' => $compTecnica->id],
                ['contenido' => 'Usa preguntas de frecuencia y cantidad para cuantificar la ingesta']
            );

            // ── Sesión de ejemplo (Ana en PSI-301, estado finalizada con borrador) ──

            $sesionAna = SesionSimulacion::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'alumno_id' => $ana->id],
                [
                    'estado'          => 'finalizada',
                    'tipo'            => 'real',
                    'inicio_at'       => now()->subHour(),
                    'finalizacion_at' => now()->subMinutes(5),
                ]
            );

            foreach ([
                ['emisor' => 'agente',  'contenido' => 'Buenos días. Me ha mandado el médico... aunque no sé muy bien para qué sirve esto.', 'orden' => 1],
                ['emisor' => 'alumno',  'contenido' => '¿Puede contarme qué le trae por aquí hoy?', 'orden' => 2],
                ['emisor' => 'agente',  'contenido' => 'Pues... me han dicho que tengo ansiedad. Llevo meses sin dormir bien y me pongo muy nervioso en el trabajo.', 'orden' => 3],
                ['emisor' => 'alumno',  'contenido' => '¿Desde cuándo nota estos síntomas?', 'orden' => 4],
                ['emisor' => 'agente',  'contenido' => 'Unos seis meses. Desde que cambiaron la dirección en mi empresa, la verdad.', 'orden' => 5],
                ['emisor' => 'alumno',  'contenido' => '¿Cómo está siendo esa situación en el trabajo?', 'orden' => 6],
                ['emisor' => 'agente',  'contenido' => 'Bueno... hay ciertos cambios que no me convencen. Preferiría no entrar en detalles.', 'orden' => 7],
            ] as $msg) {
                Mensaje::updateOrCreate(
                    ['sesion_simulacion_id' => $sesionAna->id, 'orden' => $msg['orden']],
                    ['emisor' => $msg['emisor'], 'contenido' => $msg['contenido']]
                );
            }

            // Resultado de Ana: borrador listo (CU-24 puede cargarlo y publicarlo)
            $resultadoAna = Resultado::updateOrCreate(
                ['sesion_simulacion_id' => $sesionAna->id],
                [
                    'estado'                      => 'procesando',
                    'borrador_resumen'            => 'La alumna realizó una entrevista correcta con buenas preguntas abiertas iniciales. Detectó la ansiedad pero no profundizó en el origen laboral.',
                    'borrador_calificacion'       => 6.5,
                    'borrador_feedback'           => 'Buena técnica de preguntas abiertas. Mejorar la persistencia cuando el paciente evita responder directamente.',
                    'borrador_competencias'       => [
                        ['competencia_id' => $compTecnica->id,   'puntuacion' => 7.0, 'comentario' => 'Buenas preguntas iniciales, falta profundidad'],
                        ['competencia_id' => $compCobertura->id, 'puntuacion' => 6.0, 'comentario' => 'No exploró el ámbito laboral suficientemente'],
                    ],
                    'borrador_mapa_descubrimiento' => [
                        'descubierto'    => ['Ansiedad diagnosticada', 'Insomnio', 'Cambio de dirección laboral'],
                        'no_descubierto' => ['Conflicto con jefe', 'Miedo a perder empleo'],
                    ],
                ]
            );

            // ── Sesión pausada (Carlos en PSI-301 — para CU-27 Retomar) ──────

            $sesionCarlosPausada = SesionSimulacion::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'alumno_id' => $carlos->id],
                [
                    'estado'    => 'pausada',
                    'tipo'      => 'real',
                    'inicio_at' => now()->subHours(2),
                ]
            );

            foreach ([
                ['emisor' => 'agente', 'contenido' => 'Buenos días. Me ha mandado el médico... aunque no sé muy bien para qué sirve esto.', 'orden' => 1],
                ['emisor' => 'alumno', 'contenido' => 'Buenos días. Soy estudiante de psicología. ¿Cómo se encuentra hoy?', 'orden' => 2],
                ['emisor' => 'agente', 'contenido' => 'Más o menos... como siempre últimamente.', 'orden' => 3],
            ] as $msg) {
                Mensaje::updateOrCreate(
                    ['sesion_simulacion_id' => $sesionCarlosPausada->id, 'orden' => $msg['orden']],
                    ['emisor' => $msg['emisor'], 'contenido' => $msg['contenido']]
                );
            }

            // ── Resultado evaluado (CU-30: alumno puede ver su nota publicada) ──

            // Sesión de Laura (PSI-301) ya finalizada y evaluada — para que CU-30 tenga datos
            $sesionLaura = SesionSimulacion::updateOrCreate(
                ['escenario_id' => $escPsi->id, 'alumno_id' => $laura->id],
                [
                    'estado'          => 'evaluada',
                    'tipo'            => 'real',
                    'inicio_at'       => now()->subDays(2),
                    'finalizacion_at' => now()->subDays(2)->addHour(),
                ]
            );

            Mensaje::updateOrCreate(
                ['sesion_simulacion_id' => $sesionLaura->id, 'orden' => 1],
                ['emisor' => 'agente', 'contenido' => 'Buenos días. Me ha mandado el médico... aunque no sé muy bien para qué sirve esto.']
            );
            Mensaje::updateOrCreate(
                ['sesion_simulacion_id' => $sesionLaura->id, 'orden' => 2],
                ['emisor' => 'alumno', 'contenido' => '¿Puede contarme qué síntomas le están afectando más en su día a día?']
            );
            Mensaje::updateOrCreate(
                ['sesion_simulacion_id' => $sesionLaura->id, 'orden' => 3],
                ['emisor' => 'agente', 'contenido' => 'El insomnio sobre todo. Y la tensión en el trabajo... eso está muy mal últimamente.']
            );

            Resultado::updateOrCreate(
                ['sesion_simulacion_id' => $sesionLaura->id],
                [
                    'estado'                      => 'evaluado',
                    'borrador_resumen'            => 'Alumna con buena capacidad de escucha. Detectó rápidamente el origen laboral del problema.',
                    'borrador_calificacion'       => 8.0,
                    'borrador_feedback'           => 'Excelente manejo de la entrevista.',
                    'borrador_competencias'       => [
                        ['competencia_id' => $compTecnica->id,   'puntuacion' => 8.0],
                        ['competencia_id' => $compCobertura->id, 'puntuacion' => 8.0],
                    ],
                    'borrador_mapa_descubrimiento' => ['descubierto' => ['Ansiedad', 'Conflicto laboral'], 'no_descubierto' => []],
                    'final_calificacion'          => 8.5,
                    'final_feedback'              => 'Muy buena entrevista. Técnica de preguntas excelente. Detectó el origen laboral en pocos intercambios.',
                    'final_competencias'          => [
                        ['competencia_id' => $compTecnica->id,   'puntuacion' => 9.0, 'comentario' => 'Preguntas muy acertadas'],
                        ['competencia_id' => $compCobertura->id, 'puntuacion' => 8.0, 'comentario' => 'Cobertura completa'],
                    ],
                    'publicado_at'               => now()->subDay(),
                ]
            );

        }); // fin DB::transaction

        $this->command->info('✅ DemoSeeder COMPLETO: todos los modelos sembrados');
        $this->command->info('   Credenciales: *@omnisim.test / password');
        $this->command->table(
            ['Módulo', 'Datos creados'],
            [
                ['Usuarios',    '1 admin + 2 profesores + 5 alumnos'],
                ['Asignaturas', 'PSI-301 (García) · INF-401 (López) · DIT-201 (García)'],
                ['Matrículas',  '8 matrículas cruzadas'],
                ['Escenarios',  '3 publicados con PerfilAgente completo + criterios'],
                ['Sesiones',    '3 sesiones: Ana(finalizada) · Carlos(pausada) · Laura(evaluada)'],
                ['Resultados',  'Ana→procesando(CU-24) · Laura→evaluado+publicado(CU-30)'],
            ]
        );
        $this->command->info('');
        $this->command->info('📋 Flujos disponibles para testing:');
        $this->command->info('   CU-25/26: ana@omnisim.test → PSI-301 → Entrevista con paciente ansioso');
        $this->command->info('   CU-27:    carlos@omnisim.test → sesión pausada en PSI-301');
        $this->command->info('   CU-24:    garcia@omnisim.test → cargar borrador de Ana (resultado procesando)');
        $this->command->info('   CU-30:    laura@omnisim.test → ver nota publicada (8.5)');
        $this->command->info('   INF-401:  carlos/pedro/elena → Entrevista con stakeholder');
    }
}
