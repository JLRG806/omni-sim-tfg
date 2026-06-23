# Contratos de Modelos — OmniSim

**Fuente de verdad** para nombres de tabla, relaciones, ENUMs y FKs.
Consultar antes de escribir cualquier controller, test o FormRequest.

---

## ⚠️ Tablas con nombre no estándar (requieren `$table` explícita)

Laravel pluraliza incorrectamente estas clases. Siempre tienen `protected $table`:

| Clase | Tabla real | Laravel generaría (MAL) |
|---|---|---|
| `ObjetivoAprendizaje` | `objetivos_aprendizaje` | `objetivo_aprendizajes` |
| `PerfilAgente` | `perfiles_agente` | `perfil_agentes` |
| `CriterioEvaluacion` | `criterios_evaluacion` | `criterio_evaluacions` |
| `SesionSimulacion` | `sesiones_simulacion` | `sesion_simulacions` |

Tablas estándar (no necesitan `$table`): `users`, `asignaturas`, `matriculas`, `escenarios`, `competencias`, `mensajes`, `resultados`.

---

## ENUMs — valores válidos

| Modelo | Campo | Valores permitidos |
|---|---|---|
| `User` | `rol` | `admin` · `profesor` · `alumno` |
| `User` | `estado` | `activo` · `inactivo` |
| `Escenario` | `estado` | `borrador` · `publicado` |
| `PerfilAgente` | `tono_emocional` | `formal` · `amigable` · `empatico` · `serio` · `distante` |
| `PerfilAgente` | `nivel_dificultad` | `facil` · `medio` · `dificil` |
| `Competencia` | `tipo` | `universal` · `personalizada` |
| `SesionSimulacion` | `estado` | `en_curso` · `pausada` · `procesando` · `finalizada` · `evaluada` |
| `Resultado` | `estado` | `pendiente` · `procesando` · `evaluado` |
| `Mensaje` | `emisor` | `alumno` · `agente` |

---

## Relaciones (nombres exactos de métodos)

### `User`
```php
->asignaturas()          // HasMany → Asignatura (FK: profesor_id)
->escenariosCreados()    // HasMany → Escenario  (FK: profesor_id)
->matriculas()           // HasMany → Matricula  (FK: alumno_id)
->sesiones()             // HasMany → SesionSimulacion (FK: alumno_id)
```

### `Asignatura`
```php
->profesor()             // BelongsTo → User        (FK: profesor_id)
->matriculas()           // HasMany   → Matricula   (FK: asignatura_id)
->escenarios()           // HasMany   → Escenario   (FK: asignatura_id)
```

### `Matricula`
```php
->alumno()               // BelongsTo → User        (FK: alumno_id)
->asignatura()           // BelongsTo → Asignatura  (FK: asignatura_id)
```

### `Escenario`
```php
->asignatura()           // BelongsTo → Asignatura  (FK: asignatura_id)
->profesor()             // BelongsTo → User        (FK: profesor_id)
->perfilAgente()         // HasOne    → PerfilAgente (FK: escenario_id)
->objetivos()            // HasMany   → ObjetivoAprendizaje (FK: escenario_id) — orderBy('orden')
->competenciasPersonalizadas() // HasMany → Competencia (FK: escenario_id)
->sesiones()             // HasMany   → SesionSimulacion   (FK: escenario_id)
```

### `ObjetivoAprendizaje`
```php
->escenario()            // BelongsTo → Escenario (FK: escenario_id)
```

### `PerfilAgente`
```php
->escenario()            // BelongsTo → Escenario       (FK: escenario_id)
->criterios()            // HasMany   → CriterioEvaluacion (FK: perfil_agente_id)
// JSON casts: informacion_explicita[], informacion_latente[], restricciones[]
```

### `Competencia`
```php
->escenario()            // BelongsTo → Escenario (FK: escenario_id — null si universal)
->criterios()            // HasMany   → CriterioEvaluacion (FK: competencia_id)
```

### `CriterioEvaluacion`
```php
->perfilAgente()         // BelongsTo → PerfilAgente (FK: perfil_agente_id)
->competencia()          // BelongsTo → Competencia  (FK: competencia_id)
```

### `SesionSimulacion`
```php
->alumno()               // BelongsTo → User    (FK: alumno_id)
->escenario()            // BelongsTo → Escenario (FK: escenario_id)
->mensajes()             // HasMany   → Mensaje  (FK: sesion_simulacion_id) — orderBy('orden')
->resultado()            // HasOne    → Resultado (FK: sesion_simulacion_id)
```

### `Mensaje`
```php
// ⚠️ SIN updated_at (TIMESTAMPS_DISABLED o $timestamps = false en el modelo)
->sesion()               // BelongsTo → SesionSimulacion (FK: sesion_simulacion_id)
//   ↑ NO usar sesionSimulacion() — ese método NO existe
```

### `Resultado`
```php
->sesion()               // BelongsTo → SesionSimulacion (FK: sesion_simulacion_id)
//   ↑ NO usar sesionSimulacion() — ese método NO existe
// JSON casts: borrador_mapa_descubrimiento[], borrador_competencias[], final_competencias[]
```

---

## FK columns no estándar (frecuentemente confundidas)

| Modelo | FK Column | Apunta a |
|---|---|---|
| `Matricula` | `alumno_id` | `users.id` donde `rol=alumno` |
| `SesionSimulacion` | `alumno_id` | `users.id` donde `rol=alumno` |
| `SesionSimulacion` | `sesion_simulacion_id` en Resultado/Mensaje | (FK inversa) |
| `PerfilAgente` | `perfil_agente_id` en CriterioEvaluacion | (FK inversa) |
| `Escenario` | `profesor_id` | `users.id` donde `rol=profesor` |

---

## Bugs históricos capturados con este contrato

| CU | Bug | Causa | Fix |
|---|---|---|---|
| CU-18 | `objetivo_aprendizajes` → table not found | Falta `$table` en ObjetivoAprendizaje | `$table = 'objetivos_aprendizaje'` |
| CU-18 | `perfil_agentes` → table not found | Falta `$table` en PerfilAgente | `$table = 'perfiles_agente'` |
| CU-18 | `criterio_evaluacions` → table not found | Falta `$table` en CriterioEvaluacion | `$table = 'criterios_evaluacion'` |
| CU-21 | `sesion_simulacions` → table not found | Falta `$table` en SesionSimulacion | `$table = 'sesiones_simulacion'` |
| CU-24 | `$resultado->sesionSimulacion` → null | Relación se llama `sesion()` | Usar `$resultado->sesion` |
| CU-24 | `estado='finalizada'` en Resultado | ENUM solo permite `pendiente\|procesando\|evaluado` | Usar `'procesando'` |
