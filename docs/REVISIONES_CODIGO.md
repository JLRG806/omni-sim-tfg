# Revisiones de Código — OmniSim TFG

Registro de hallazgos encontrados durante las sesiones de code review (`/code-review`) a lo largo del desarrollo.
Útil para el **Capítulo 5 (Pruebas)** de la memoria del TFG como evidencia del proceso de calidad.

---

## Resumen ejecutivo

| Sesión | CUs revisados | Hallazgos | Corregidos |
|--------|--------------|-----------|-----------|
| 1 | CU-01..07 (Transversal + Admin) | 8 | 8 |
| 2 | CU-09 (listarAsignaturas) | 2 | 2 |
| 3 | CU-11 (modificarAsignatura) | 2 | 2 (auto) |
| 4 | CU-12 (eliminarAsignatura) | 0 | — |
| 5 | CU-10..15 (módulo Asignaturas + Profesor) | 5 | 4 + 1 anotado |
| 6 | CU-16 (desmatricularAlumno) | 2 | 2 |
| **Total** | **CU-01..17** | **19** | **18 (+1 work future)** |

---

## Sesión 1 — CU-01..07 (23 Jun 2026)

**Scope:** Módulo Transversal (auth) + Módulo Admin (usuarios).

| # | Sev | Archivo | Línea | Hallazgo | Fix |
|---|-----|---------|-------|----------|-----|
| 1 | 🔴 CRÍTICO | `loginController.php` | 28 | Usuarios `estado=inactivo` podían autenticarse y obtener tokens Sanctum | Añadido check `$user->estado === 'inactivo'` → 403 |
| 2 | 🔴 ALTO | `eliminarUsuarioController.php` | 28 | Soft-delete no revocaba los tokens Sanctum del usuario eliminado | Añadido `$usuario->tokens()->delete()` antes del delete |
| 3 | 🔴 ALTO | `recuperarCuentaController.php` | 52 | Reset de contraseña no revocaba tokens — atacante con token robado mantenía acceso | Añadido `$user->tokens()->delete()` en el callback |
| 4 | 🔴 ALTO | `recuperarCuentaController.php` | 57 | `Hash::make()` explícito + cast `hashed` → doble hash (simplificación, no bug real) | Eliminado `Hash::make()` redundante (cast lo hace automáticamente) |
| 5 | 🟠 MEDIO | `eliminarUsuarioController.php` | 27 | Admin podía auto-eliminarse; token seguía válido post-delete | Añadido guard 422 si `$usuario->id === $request->user()->id` |
| 6 | 🟠 MEDIO | `ModificarUsuarioRequest.php` | 27 | Regla `unique` con string interpolado — frágil si se renombra el param de ruta | Cambiado a `Rule::unique()->ignore((int) $id)` |
| 7 | 🟠 MEDIO | `axios.js` | 32 | Interceptor 401 no manejaba 403 — usuario con rol degradado quedaba atrapado | Añadido `status === 403` al interceptor |
| 8 | 🟠 MEDIO | `UsuarioFormView.vue` | 177 | Vue Router reutiliza componente: `onMounted` no re-ejecuta en editar→crear | Reemplazado `onMounted` por `watch(() => route.params.id, init, { immediate: true })` |

---

## Sesión 2 — CU-09 (23 Jun 2026)

**Scope:** `listarAsignaturasController` y `GestionAsignaturasView`.

| # | Sev | Archivo | Línea | Hallazgo | Fix |
|---|-----|---------|-------|----------|-----|
| 1 | 🟠 MEDIO | `listarAsignaturasController.php` | 33 | Profesor soft-deleted mostraba `profesor: null` sin indicación — asignatura parecía sin profesor | Eager load con `withTrashed()` + campo `eliminado: bool` en respuesta |
| 2 | 🟠 MEDIO | `router/index.js` | 55 | Rutas `/admin/asignaturas/nueva` y `/:id/editar` no registradas — Vue Router fallaba silenciosamente | Añadidos placeholders `AsignaturaFormView` con rutas |

---

## Sesión 3 — CU-11 (23 Jun 2026)

**Scope:** `modificarAsignaturaController`.

| # | Sev | Archivo | Línea | Hallazgo | Fix |
|---|-----|---------|-------|----------|-----|
| 1 | 🟠 MEDIO | `modificarAsignaturaController.php` | 38 | `$asignatura->profesor` podía ser null si profesor estaba soft-deleted → crash | Añadido null check ternario en la respuesta |
| 2 | 🟡 BAJO | `modificarAsignaturaController.php` | 31 | `descripcion ?? $asignatura->descripcion` no manejaba campo NOT NULL ante null explícito | Cambiado a `?? $asignatura->descripcion ?? ''` |

*Nota: ambos fixes fueron aplicados automáticamente por el linter/usuario antes de la revisión.*

---

## Sesión 4 — CU-12 (23 Jun 2026)

**Scope:** `eliminarAsignaturaController`.

```json
[]
```

Sin hallazgos. Implementación correcta e idéntica al patrón de CU-07 (eliminarUsuarioController).

---

## Sesión 5 — CU-10..15 (23 Jun 2026)

**Scope:** Módulo Asignaturas (CU-10..13) + Módulo Profesor (CU-14..15).

| # | Sev | Archivo | Línea | Hallazgo | Fix |
|---|-----|---------|-------|----------|-----|
| 1 | 🔴 ALTO | `matricularAlumnoController.php` | 32 | Cualquier profesor podía matricular alumnos en asignaturas ajenas — falta check `profesor_id` | Añadido guard 403 si `$asignatura->profesor_id !== $request->user()->id` |
| 2 | 🔴 ALTO | `buscarAlumnoController.php` | 33 | Cualquier profesor podía ver lista de alumnos de asignaturas ajenas | Añadido guard 403 igual que #1 |
| 3 | 🟠 MEDIO | `crearAsignaturaController.php` | 42 | Null deref TOCTOU: profesor podía ser soft-deleted entre validación y INSERT | Añadido null check ternario en la respuesta (`$asignatura->profesor ? [...] : null`) |
| 4 | 🟠 MEDIO | `DemoSeeder.php` | 36 | `SET CONSTRAINTS ALL DEFERRED` era no-op fuera de transacción PostgreSQL | Envuelto el cuerpo del seeder en `DB::transaction()` |
| 5 | 🟡 INFO | `eliminarAsignaturaController.php` | 27 | Soft-delete de asignatura deja Matriculas huérfanas (sin cascade) | Anotado como work future Cap. 6 — decisión de diseño TFG |

---

## Sesión 6 — CU-16 (23 Jun 2026)

**Scope:** `desmatricularAlumnoController` y `DemoSeeder`.

| # | Sev | Archivo | Línea | Hallazgo | Fix |
|---|-----|---------|-------|----------|-----|
| 1 | 🔴 ALTO | `desmatricularAlumnoController.php` | 32 | Cualquier profesor podía desmatricular alumnos de asignaturas ajenas | Añadido guard 403 con check `$matricula->asignatura->profesor_id !== $request->user()->id` |
| 2 | 🟠 MEDIO | `DemoSeeder.php` | 36 | `SET CONSTRAINTS ALL DEFERRED` fuera de transacción — no-op en auto-commit | Fix idéntico al de Sesión 5: `DB::transaction()` |

---

## Patrones recurrentes identificados

1. **Ownership sin verificar en operaciones de profesor** — los controllers CU-15, CU-16, CU-17 necesitaban check `asignatura->profesor_id === user->id`. Patrón a aplicar en todos los CUs de profesor que reciban un `asignatura_id` o `matricula_id`.

2. **Revocación de tokens al modificar credenciales** — cualquier operación que cambie estado de seguridad (eliminar usuario, cambiar contraseña) debe revocar tokens activos.

3. **Null deref tras eager load con SoftDeletes** — cuando un modelo relacionado puede estar soft-deleted, siempre usar `withTrashed()` en el eager load Y null check ternario en la respuesta.

4. **`ConvertEmptyStringsToNull` + regla `string`** — todos los campos opcionales de tipo string necesitan `nullable` en la validación si pueden recibir string vacío desde el frontend.

---

*Última actualización: 23 Jun 2026 — CU-01..17 revisados*
