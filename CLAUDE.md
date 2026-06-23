# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# OmniSim — Plataforma de Simulación Multidisciplinar con IA

TFG. Plataforma web SPA donde estudiantes practican entrevistas profesionales con stakeholders simulados por IA. Multidisciplinar (Informática, Psicología, Dietética, Civil, etc.). El profesor configura el escenario y el perfil del agente; el alumno conversa; la IA pre-evalúa y el profesor valida.

---

## Stack tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend SPA | Vue 3 + Vite | 3.x / 5.x |
| Estilos | Tailwind CSS | 3.x |
| Estado | Pinia | 2.x |
| HTTP | Axios | 1.x |
| Backend | Laravel + PHP | 11.x / 8.4 |
| Auth | Laravel Sanctum | 4.x |
| ORM | Eloquent | (Laravel) |
| Cola async | Laravel Queue (Database driver) | (Laravel) |
| BD | PostgreSQL | 16 |
| Orquestador IA | n8n + Ollama | última |
| Servidor web | Nginx | 1.25 |
| Contenedores | Docker + Compose | 24 / 2.x |

**Decisión clave:** sin Redis (database driver es suficiente para 50 usuarios concurrentes).

---

## Estructura del repo

```
omni-sim-tfg/
├── docs/                          # Toda la documentación del TFG
│   ├── disciplinaDeRequisitos/    # Cap. 3: requisitos, CU, wireframes
│   ├── modeloDeDominio/           # Cap. 3: clases, estados
│   ├── analisisYDiseño/           # Cap. 4: MVC, ER, arquitectura, despliegue
│   └── prototipos/                # Cap. 3.6: 19 wireframes HTML + PNG
│
├── backend/                       # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/      # 30 controladores (1 por CU)
│   │   ├── Models/                # 11 modelos (STI en User)
│   │   └── Services/              # Lógica de negocio (incluye OrquestadorIAService)
│   ├── database/
│   │   ├── migrations/            # 17 tablas
│   │   └── seeders/               # Datos demo
│   └── routes/api.php             # Rutas /api/v1/*
│
├── frontend/                      # Vue SPA
│   ├── src/
│   │   ├── views/                 # 17 vistas (corresponden a wireframes)
│   │   ├── components/            # Componentes reutilizables (ModalConfirmacion, etc.)
│   │   ├── stores/                # Pinia stores
│   │   └── router/                # Vue Router
│   └── vite.config.js
│
├── n8n/                           # Workflows JSON exportados
├── docker/                        # Dockerfiles + nginx.conf
├── docker-compose.yml             # Producción
├── docker-compose.dev.yml         # Desarrollo (hot reload)
├── CLAUDE.md                      # Este archivo
└── README.md
```

---

## Convenciones

### Backend (Laravel)

- **Controladores: 1 por caso de uso.** Nombres en camelCase con sufijo `Controller`:
  - `loginController`, `crearUsuarioController`, `enviarMensajeController`, etc.
- **Rutas:** `/api/v1/[recurso]`. Ejemplo: `POST /api/v1/auth/login`, `POST /api/v1/sesiones/{id}/mensajes`
- **Modelos:** PascalCase singular: `User`, `Asignatura`, `Escenario`, `PerfilAgente`, `SesionSimulacion`, `Mensaje`, `Resultados`
- **STI en `users`:** columna `rol` enum (`admin`/`profesor`/`alumno`). NO crear tablas separadas
- **JSON columns** (no normalizar): `perfiles_agente.informacion_explicita`, `informacion_latente`, `restricciones`; `resultados.borrador_*` y `final_*`
- **Tests:** Feature tests por CU en `tests/Feature/CU/CU01LoginTest.php`, etc.
- **Comentarios PHP:** usar PHPDoc en clases, propiedades y métodos públicos/protegidos.
  - Clases: `@property` para atributos, `@property-read` para relaciones virtuales.
  - Métodos: `@param`, `@return` con tipos fully-qualified cuando no son obvios por la firma.
  - Migraciones y Seeders: docblock de clase describiendo qué tabla/datos crea.
  - Ejemplo:
    ```php
    /**
     * Obtiene las asignaturas coordinadas por este profesor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Asignatura>
     */
    public function asignaturas(): HasMany { ... }
    ```

### Frontend (Vue)

- **Vistas en `src/views/`:** 1 archivo `.vue` por wireframe. Nombres: `LoginView.vue`, `SimulacionChatView.vue`, etc.
- **Stores Pinia** por dominio: `authStore`, `escenarioStore`, `sesionStore`
- **Tailwind:** utility-first, evitar CSS custom innecesario
- **Componentes reutilizables en `src/components/`:** `ModalConfirmacion.vue`, `Avatar.vue`, etc.

### Terminología

Usar siempre **Profesor** (no Docente) y **Alumno** (no Estudiante) en código, comentarios y documentación.

### Mensajes de commit

Formato convencional con prefijo de scope:
```
feat(backend): añadir loginController y test CU-01
fix(frontend): corregir validación email en LoginView
docs: actualizar diagrama 4.2 ER con cambio en perfiles_agente
chore(docker): añadir healthcheck a postgres
```

---

## Comandos clave

### Levantar entorno de desarrollo
```bash
docker compose -f docker-compose.dev.yml up -d
docker compose exec backend php artisan migrate:fresh --seed
```

### Tests
```bash
docker compose exec backend php artisan test
docker compose exec frontend npm run test
```

### Frontend en modo dev (hot reload)
```bash
cd frontend && npm run dev    # localhost:5173
```

### Acceso a contenedores
```bash
docker compose exec backend bash         # Laravel
docker compose exec postgres psql -U omnisim
```

---

## Esquema de BD (resumen — 17 tablas)

| Tabla | Propósito clave |
|-------|----------------|
| `users` (STI) | Usuarios con `rol` enum |
| `password_reset_tokens` | CU-03 |
| `personal_access_tokens` | Sanctum |
| `migrations`, `jobs`, `failed_jobs` | Laravel infra |
| `asignaturas` | Coordinada por 1 profesor |
| `matriculas` | Pivot alumno↔asignatura (NO soft delete) |
| `escenarios` | Estado: `borrador`/`publicado` |
| `objetivos_aprendizaje` | Lista de objetivos por escenario |
| `perfiles_agente` | 16 campos (3 JSON), 1:1 con escenario |
| `competencias` | 5 universales + personalizadas por escenario |
| `criterios_evaluacion` | Asocia perfil_agente ↔ competencia |
| `sesiones_simulacion` | 5 estados: en_curso/pausada/procesando/finalizada/evaluada |
| `mensajes` | Por sesión, emisor: alumno/agente |
| `resultados` | `borrador_*` (IA) + `final_*` (profesor), 1:1 con sesión |

ER completo: `docs/analisisYDiseño/4.2_diagrama_entidad_relacion.puml`

### Campos de Escenario + PerfilAgente (implementar en CU-18/CU-19)

**Fase 1 — Escenario (4 campos):**
1. Título del escenario
2. Área de conocimiento (disciplina)
3. Describe la situación (contexto)
4. ¿Qué debe aprender el alumno? (→ `objetivos_aprendizaje`)

**Fase 2 — PerfilAgente (7 campos):**
5. ¿Quién es el personaje? (rol/identidad)
6. ¿Cuál es su historia? (trasfondo)
7. ¿Qué sabe y qué no sabe? (conocimientos)
8. ¿Qué dice abiertamente? (→ `informacion_explicita` JSON)
9. ¿Qué oculta o cuesta decir? (→ `informacion_latente` JSON)
10. ¿Cómo se comunica? (comportamiento/personalidad)
11. Dificultad: Fácil/Medio/Difícil (→ `restricciones` JSON con instrucciones de prompt)

**Evaluación (1 campo):**
12. ¿Cómo evalúas al alumno? (→ `criterios_evaluacion`)

**Lógica de dificultad** (el sistema añade al system prompt automáticamente):
- Fácil: cooperativo, comparte info, revela info latente con preguntas básicas
- Medio: natural, info latente solo con buenas preguntas de seguimiento
- Difícil: evasivo, respuestas vagas, info latente solo con preguntas muy específicas

---

## 30 Casos de Uso

| Módulo | CU | Estado |
|--------|----|--------|
| **Transversal** | CU-01 a CU-03 | Pendiente impl |
| **Administración** | CU-04 a CU-13 | Pendiente impl |
| **Profesor** | CU-14 a CU-24 | Pendiente impl |
| **Alumno** | CU-25 a CU-30 | Pendiente impl |

### CUs críticos arquitectónicamente

- **CU-01 Iniciar Sesión** — Sanctum + STI rol. Ver `docs/analisisYDiseño/4.1.1_colaboracion_iniciarSesion.puml`
- **CU-18 Crear Escenario** — Dos fases internas (datos escenario + perfil agente). Ver wireframes WF-09 y WF-10
- **CU-24 Emitir Calificación** — Profesor revisa borrador IA generado en CU-29. Ver `docs/analisisYDiseño/4.1.3_colaboracion_emitirCalificacion.puml`
- **CU-26 Iniciar Simulación** — Crea SesionSimulacion + carga PerfilAgente para Orquestador IA
- **CU-28 Enviar Mensaje** — Núcleo del sistema. Sincrónico con Orquestador IA. Ver `docs/analisisYDiseño/4.1.2_colaboracion_enviarMensaje.puml`
- **CU-29 Finalizar Sesión** — **Asíncrono**. Dispatcha job a la cola que llama a Orquestador IA para generar borrador

---

## Referencias de trazabilidad

Cuando trabajes en un CU, consulta antes:

| Necesitas... | Archivo |
|--------------|---------|
| Detallado del CU (flujo, alternativos) | `docs/disciplinaDeRequisitos/4. Detallado de casos de uso/[Modulo]/CU-XX_*.puml` |
| Wireframe de la pantalla | `docs/prototipos/XX_*.html` (abrir en navegador) |
| Esquema de tabla | `docs/analisisYDiseño/4.2_diagrama_entidad_relacion.puml` |
| Flujo MVC | `docs/analisisYDiseño/4.1.X_colaboracion_*.puml` (para CU-01, CU-24, CU-28) |
| Estados de usuario | `docs/modeloDeDominio/diagramaDeEstados/[Rol]/diagramaDeEstados*.puml` |

---

## Decisiones arquitectónicas (NO revisitar sin avisar)

1. **SPA, no multi-página** — Vue genera todas las vistas en cliente. Backend devuelve JSON, nunca HTML
2. **STI en User** — Una tabla `users` con `rol`, no tablas separadas Admin/Profesor/Alumno
3. **JSON columns en PerfilAgente** — Listas (info_explicita, info_latente, restricciones) como JSON. NO crear tablas relacionadas
4. **Sin Redis** — Cola async usa Database driver (tabla `jobs`). Redis es trabajo futuro
5. **CU-29 asíncrono** — Al finalizar sesión, el job se encola y el alumno vuelve al dashboard sin esperar. Orquestador IA procesa en background
6. **CU-24 valida borrador IA** — El profesor ajusta lo que la IA generó. El alumno solo ve resultados publicados
7. **Orquestador IA agnóstico** — Backend habla con n8n por REST + webhooks. Sustituir Ollama por Groq/OpenRouter no requiere tocar el backend
8. **5 competencias universales fijas + personalizables** — Técnica de preguntas, Cobertura, Descubrimiento latente, Empatía, Gestión del tiempo. El profesor añade las suyas por escenario

---

## Contratos de Modelos — referencia rápida (ver detalle en `docs/CONTRATOS_MODELOS.md`)

### Tablas NO estándar (siempre tienen `$table` explícita)
```
ObjetivoAprendizaje → objetivos_aprendizaje
PerfilAgente        → perfiles_agente
CriterioEvaluacion  → criterios_evaluacion
SesionSimulacion    → sesiones_simulacion
```

### ENUMs críticos
```
User.rol:              admin | profesor | alumno
User.estado:           activo | inactivo
Escenario.estado:      borrador | publicado
SesionSimulacion.estado: en_curso | pausada | procesando | finalizada | evaluada
Resultado.estado:      pendiente | procesando | evaluado
Mensaje.emisor:        alumno | agente
PerfilAgente.tono_emocional: formal | amigable | empatico | serio | distante
PerfilAgente.nivel_dificultad: facil | medio | dificil
```

### Relaciones — métodos EXACTOS (errores frecuentes)
```php
// ⚠️ En Resultado y Mensaje: la relación es sesion(), NO sesionSimulacion()
$resultado->sesion        // ✅ BelongsTo → SesionSimulacion
$resultado->sesionSimulacion // ❌ NO EXISTE

// FK columns no estándar
Matricula::alumno_id       → users (rol=alumno)
SesionSimulacion::alumno_id → users (rol=alumno)
PerfilAgente::criterios()  → CriterioEvaluacion (FK: perfil_agente_id)
Escenario::objetivos()     → orderBy('orden')
Escenario::sesiones()      → SesionSimulacion
SesionSimulacion::mensajes() → orderBy('orden')
```

---

## Lo que NO hacer

- ❌ NO usar Redis (decisión cerrada)
- ❌ NO hacer multi-página (es SPA)
- ❌ NO normalizar campos JSON (decisión cerrada)
- ❌ NO crear tablas Administrador/Profesor/Alumno separadas (usar STI con `rol`)
- ❌ NO modificar wireframes en `docs/prototipos/` durante implementación (son referencia)
- ❌ NO inventar campos de modelos no documentados (ver ER en `docs/analisisYDiseño/4.2_*`)
- ❌ NO llamar a Ollama directamente desde Laravel (siempre vía n8n)
- ❌ NO usar `git push --force` ni `git reset --hard` sin avisar

---

## Diagramas PlantUML — convenciones de color

Usadas en todos los `.puml` de `docs/`:

| Tipo | Color | Estereotipo |
|------|-------|-------------|
| Vista | `#B3D9FF` (azul) | `<<view>>` |
| Controlador | `#C8E6A0` (verde) | `<<ctrl>>` |
| Modelo | `#FFD9A0` (naranja) | `<<model>>` |
| Actor | `#F5F5F5` (gris) | `<<actor>>` |
| Colaboración | `#D0F0C0` (verde claro) | `<<collab>>` |
| Package | `#FEFECE` (amarillo claro) | — |
| Flechas | `#A80036` (rojo oscuro) | — |

---

## Trabajo futuro (FUERA del alcance del TFG)

Documentado en Capítulo 6:

- RAG con documentos de referencia en PerfilAgente
- Generación asistida del PerfilAgente con IA
- Métricas automáticas de evaluación (NLP)
- Importación masiva de usuarios (CSV + JSON endpoint)
- Auditoría (created_by/updated_by)
- Operaciones en lote
- Versionado de escenarios
- App móvil
- Migración a Redis (cuando escale)
- GPU dedicada en la nube

**No implementar nada de esto durante el TFG.** Si surge la tentación, anotar en Capítulo 6.

---

## Workflow Claude Code

### Skills útiles para este proyecto

- `/init` — al arrancar el chat (este archivo)
- `Plan` agent — antes de implementar un módulo grande
- `Explore` agent — para buscar dónde está algo en el repo
- `code-review` — antes de commit
- `simplify` — aplica fixes del code-review
- `verify` — tras feature, arrancar app y probar
- `run` — lanzar la app
- `security-review` — antes de la demo de defensa
- `docs` — para README final

### Custom skills sugeridas (crear en `~/.claude/skills/`)

- `/scaffold-cu CU-XX` — genera controlador + ruta + test + form request
- `/seed-demo` — reset DB + seeders con datos de wireframes
- `/omnisim-up` — levanta entorno dev completo
- `/n8n-deploy` — despliega workflow en n8n local

---

## Punto de entrada (primer paso)

1. **Setup inicial** (día 1):
   - `docker compose -f docker-compose.dev.yml up -d`
   - Configurar conexión Laravel ↔ PostgreSQL
   - Crear las 17 migraciones (ver ER)
   - Sembrar 5 competencias universales
   - Verificar `php artisan test` corre vacío

2. **Primer CU** (día 2):
   - `Plan` agent: diseñar CU-01 Iniciar Sesión
   - Implementar `loginController` + ruta + test
   - Vista `LoginView.vue` consumiendo el endpoint
   - `verify` que el flujo funciona end-to-end

3. **Iterar por módulos** (días 3-12):
   - Transversal → Admin → Profesor → Alumno
   - Por cada CU: consultar wireframe + detallado .puml + ER, implementar, test, commit

4. **Integración IA** (días 13-16):
   - Workflow n8n + Ollama
   - CU-28 (síncrono) y CU-29 (asíncrono con cola)

5. **Pruebas + demo** (días 17-25):
   - `security-review`
   - E2E del flujo completo
   - README + slides de defensa

---

## Estado del repo

- **Documentación TFG:** Completa (Cap. 3 + 4 + 5 + 6 + wireframes + diagramas)
- **Código:** Pendiente de implementar
- **Historial de decisiones de diseño:** `DECISIONES_DE_DISEÑO.md` en la raíz (contexto completo de cada decisión tomada durante el diseño)
