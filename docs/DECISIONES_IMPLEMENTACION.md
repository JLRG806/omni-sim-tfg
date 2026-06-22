# Decisiones de Implementación — OmniSim

Este documento recoge las decisiones técnicas tomadas durante la implementación del código.
Cada sección explica **qué** se hizo, **por qué** y **qué alternativa se descartó**.
Complementa el `DECISIONES_DE_DISEÑO.md` (decisiones de análisis/diseño) y el `CLAUDE.md` (convenciones de desarrollo).

---

## Día 1 — Setup inicial

### 1. Estructura de contenedores Docker (5 servicios)

**Decisión:** Se definen 5 contenedores en `docker-compose.dev.yml`:

| Contenedor  | Imagen base       | Puerto | Responsabilidad                        |
|-------------|-------------------|--------|----------------------------------------|
| `backend`   | `php:8.2-cli`     | 8000   | API Laravel (`php artisan serve`)      |
| `frontend`  | `node:22-alpine`  | 5173   | SPA Vue 3 (Vite dev server)            |
| `postgres`  | `postgres:16-alpine` | 5432 | Base de datos PostgreSQL               |
| `nginx`     | `nginx:1.25-alpine` | 80   | Reverse proxy unificador               |
| `n8n`       | `n8nio/n8n`       | 5678   | Orquestador de workflows IA            |

**Por qué separados:** El backend y el frontend son proyectos independientes con ciclos de vida distintos. Vue/Vite necesita un servidor Node con Hot Module Replacement (HMR); Laravel necesita PHP-FPM o `artisan serve`. Mezclarlos en un mismo contenedor crea una imagen no mantenible y dificulta el escalado independiente en producción.

**Alternativa descartada:** Poner Vue dentro de `resources/js/` de Laravel (monolito con `laravel-vite-plugin`). Se descartó porque contradice la decisión de diseño de SPA pura: el backend devuelve JSON, nunca HTML.

**Rol de Nginx en desarrollo:** Actúa como punto de entrada único en `localhost:80`. Las peticiones a `/api/*` las proxifica al backend (`:8000`); el resto van al frontend (`:5173`). Esto replica la arquitectura de producción desde el día 1 y evita configurar CORS durante el desarrollo.

---

### 2. Entrypoint scripts en contenedores

**Decisión:** Cada Dockerfile incluye un `entrypoint.sh` que instala dependencias si no están presentes antes de arrancar el servidor.

```sh
# backend/entrypoint.sh
if [ ! -f "vendor/autoload.php" ]; then
    composer install ...
fi
php artisan serve --host=0.0.0.0 --port=8000
```

**Por qué:** El código fuente se monta como volumen (`./backend:/var/www/html`). Si el desarrollador clona el repo y levanta Docker sin haber corrido `composer install` localmente, el contenedor se autorrepara. Lo mismo para `node_modules` en el frontend.

**Alternativa descartada:** Pre-instalar dependencias en el `Dockerfile` con `COPY composer.json . && RUN composer install`. Esto funciona en producción, pero en desarrollo invalida la caché Docker en cada cambio de dependencias, ralentizando el ciclo de iteración.

---

### 3. `SESSION_DRIVER=file` en lugar de `database`

**Decisión:** En `backend/.env`, el driver de sesión se cambia a `file`.

**Por qué:** OmniSim es una API REST con autenticación via token (Laravel Sanctum). Las sesiones HTTP clásicas no se usan. El driver `database` (por defecto en Laravel 11) requeriría que la tabla `sessions` exista y esté activa, añadiendo complejidad innecesaria. Con `file`, las sesiones se escriben en `storage/framework/sessions/` y no requieren migraciones adicionales.

---

### 4. Instalación manual de `laravel/sanctum`

**Decisión:** Se añadió Sanctum vía `composer require laravel/sanctum`.

**Por qué:** Laravel 11 ya no incluye Sanctum por defecto en `composer.json` (a diferencia de Laravel 10). En el proyecto es imprescindible para:
- Generar tokens de API por usuario (autenticación stateless).
- Proteger las rutas `/api/v1/*` con el middleware `auth:sanctum`.
- Permitir que la SPA Vue adjunte el token en cabeceras `Authorization: Bearer`.

**Configuración en `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi(); // habilita Sanctum como guardia de API
})
```

---

### 5. Migraciones: orden y estrategia de claves foráneas

**Decisión:** Las 10 tablas de dominio se crean en migraciones separadas con timestamps que garantizan el orden de ejecución:

```
0001_01_01_000000  → users (modificada, base de todo)
0001_01_01_000003  → personal_access_tokens (Sanctum)
2025_01_01_000010  → asignaturas      (FK: users)
2025_01_01_000020  → matriculas       (FK: users, asignaturas)
2025_01_01_000030  → escenarios       (FK: asignaturas, users)
2025_01_01_000040  → objetivos_aprendizaje (FK: escenarios)
2025_01_01_000050  → perfiles_agente  (FK: escenarios, UNIQUE)
2025_01_01_000060  → competencias     (FK: escenarios NULLABLE)
2025_01_01_000070  → criterios_evaluacion (FK: perfiles_agente, competencias)
2025_01_01_000080  → sesiones_simulacion  (FK: users, escenarios)
2025_01_01_000090  → mensajes         (FK: sesiones_simulacion)
2025_01_01_000100  → resultados       (FK: sesiones_simulacion, UNIQUE)
```

**Por qué este orden:** PostgreSQL valida las claves foráneas en el momento de `CREATE TABLE`. Si `matriculas` se crea antes que `asignaturas`, la migración falla porque la tabla referenciada aún no existe.

**Modificación de la migración base de `users`:** En lugar de crear una migración `ALTER TABLE` posterior, se modificó directamente la migración `0001_01_01_000000` original porque en este punto la base de datos está vacía y es más limpio tener la definición completa en un solo lugar.

**Campos añadidos a `users`:**
- `rol ENUM('admin','profesor','alumno')` — STI (Single Table Inheritance).
- `estado ENUM('activo','inactivo') DEFAULT 'activo'` — control de acceso sin borrar el registro.
- `avatar_path VARCHAR NULLABLE` — foto de perfil opcional.
- `deleted_at TIMESTAMP NULLABLE` — soft delete (el usuario se desactiva, no se elimina).
- **Eliminado:** `remember_token` — no aplica con autenticación por token Sanctum.

**FK nullable en `competencias.escenario_id`:**
```php
$table->foreignId('escenario_id')->nullable()->constrained('escenarios');
```
Las competencias universales (las 5 fijas del sistema) tienen `escenario_id = NULL`. Las personalizadas por el profesor apuntan a un escenario concreto. PostgreSQL admite `NULL` en columnas con restricción de FK.

**Nota sobre nombres de tabla en `constrained()`:** El método `->foreignId('perfil_agente_id')->constrained()` inferiría la tabla `perfil_agentes` (incorrecto). Por eso se pasa el nombre explícito: `->constrained('perfiles_agente')`. Lo mismo para `sesion_simulacion_id` → `->constrained('sesiones_simulacion')`.

---

### 6. Modelo `Mensaje`: solo `created_at`, sin `updated_at`

**Decisión:** En el modelo `Mensaje` se define:
```php
const UPDATED_AT = null;
```

**Por qué:** Los mensajes de una simulación son inmutables una vez enviados — no se editan. El ER no incluye `updated_at` en la tabla `mensajes`. Al definir `UPDATED_AT = null`, Eloquent sigue gestionando `created_at` automáticamente en la inserción, pero no intenta escribir `updated_at` (que no existe en la tabla).

**Alternativa descartada:** `public $timestamps = false` — desactiva ambos timestamps, obligando a asignar `created_at` manualmente en cada inserción, lo que es más propenso a errores.

---

### 7. Campos JSON en `PerfilAgente` y `Resultado`: cast a `array`

**Decisión:** Los campos `informacion_explicita`, `informacion_latente`, `restricciones` (en `PerfilAgente`) y los campos `borrador_*` / `final_*` de tipo JSON (en `Resultado`) se castean a `array` en los modelos Eloquent.

**Por qué:** El cast `'array'` hace que Eloquent serialice/deserialice automáticamente entre el JSON de PostgreSQL y el array PHP. Sin el cast, el campo llegaría como string JSON crudo y el controlador tendría que hacer `json_decode()` manualmente en cada acceso.

**Implicación en PostgreSQL:** El tipo de columna es `JSON` (no `JSONB`). `JSONB` permite indexación avanzada, pero para el volumen de OmniSim (simulaciones universitarias, ~50 usuarios) no es necesario. Se puede migrar a `JSONB` en el futuro si se requiere búsqueda dentro del JSON.

---

### 8. `UserFactory` con métodos de estado (state methods)

**Decisión:** La `UserFactory` define métodos de estado encadenables: `profesor()`, `admin()`, `inactivo()`, `unverified()`.

```php
User::factory()->profesor()->create();
User::factory()->alumno()->inactivo()->create();
```

**Por qué:** Los tests de Feature necesitan crear usuarios de distintos roles sin repetir la configuración en cada test. Los state methods siguen el patrón de Laravel para factories y permiten combinarlos (ej.: `profesor()->inactivo()`). El rol por defecto es `alumno` porque es el rol más común en los tests de los CU de alumno (CU-25 a CU-30).

**Eliminado del factory original:** `remember_token` — no existe en la tabla `users` modificada; su presencia causaría un error de columna desconocida al intentar insertar.

---

### 9. `CompetenciasUniversalesSeeder`: idempotencia con `firstOrCreate`

**Decisión:** El seeder usa `firstOrCreate` en lugar de `insert` o `create`.

```php
Competencia::firstOrCreate(
    ['nombre' => $datos['nombre'], 'tipo' => 'universal'],
    ['descripcion' => ..., 'escenario_id' => null]
);
```

**Por qué:** `migrate:fresh --seed` (que se ejecuta durante el desarrollo repetidamente) vuelve a lanzar todos los seeders desde cero. Si el seeder usara `create()`, fallaría en la segunda ejecución por no encontrar la restricción UNIQUE (no la hay) o simplemente duplicaría registros. Con `firstOrCreate`, si la competencia ya existe, no la duplica; si no existe, la inserta. El seeder es **idempotente**: se puede ejecutar N veces con el mismo resultado.

---

### 10. Convención PHPDoc en todo el código PHP

**Decisión:** Todo el código PHP del proyecto lleva PHPDoc en clases, propiedades y métodos públicos/protegidos.

**Por qué:** Facilita la navegación en el IDE (autocompletado de relaciones Eloquent, tipos de retorno), hace que el código sea autodocumentado para el tribunal, y es la convención estándar en proyectos Laravel profesionales. Los `@property` en las clases de modelo documentan los atributos que Eloquent gestiona dinámicamente (no son propiedades PHP reales) y ayudan al IDE a inferir tipos.

---

### 11. Configuración del frontend Vue 3 + Vite

**Tailwind CSS 3.x:**
Se eligió la versión 3.x (no la 4.x en alpha) por estabilidad. Se configuran las rutas `content` en `tailwind.config.js` para que Tailwind elimine en producción las clases no usadas (tree-shaking de CSS).

**`vite.config.js` con `host: '0.0.0.0'`:**
El servidor Vite debe escuchar en todas las interfaces de red del contenedor (no solo `localhost`) para que Nginx pueda proxificarlo desde otro contenedor en la red Docker. Sin este ajuste, el contenedor Vite escucha solo en el loopback interno y Nginx no puede alcanzarlo.

**Alias `@` → `/src`:**
El alias `@` permite importar `@/stores/authStore` en lugar de `../../stores/authStore`. Es una convención estándar en proyectos Vue que evita rutas relativas frágiles al mover archivos.

**Pinia como gestor de estado:**
Elegido sobre Vuex (el anterior estándar de Vue) por ser la solución oficial de Vue 3. Su API es más simple y está basada en la Composition API. Cada store corresponde a un dominio: `authStore` (sesión), `escenarioStore`, `sesionStore`.

**Axios con `baseURL: '/api/v1'`:**
Toda llamada HTTP parte de `/api/v1/`, que Nginx redirige al backend. Al usar rutas relativas (sin hostname), el mismo código funciona en desarrollo (Nginx en `:80`) y en producción sin ningún cambio.

---

## Glosario técnico para el jurado

| Término | Explicación breve |
|---------|-------------------|
| **STI (Single Table Inheritance)** | Un único modelo `User` con una columna `rol` que distingue Admin, Profesor y Alumno. Evita crear tablas separadas con campos casi idénticos. |
| **Soft delete** | En lugar de borrar una fila de la BD, se rellena `deleted_at` con la fecha. Eloquent filtra automáticamente estos registros en todas las consultas. Permite restaurar datos borrados por error. |
| **Factory (Laravel)** | Clase que genera instancias del modelo con datos falsos (Faker) para tests. Evita escribir arrays de atributos repetitivos en cada test. |
| **Seeder** | Script que inserta datos iniciales en la BD. Se ejecuta con `php artisan db:seed`. Útil para datos fijos del sistema (las 5 competencias universales) y datos de demo. |
| **Sanctum** | Paquete oficial de Laravel para autenticación ligera. Genera tokens de API almacenados en la tabla `personal_access_tokens`. El cliente adjunta el token en `Authorization: Bearer <token>`. |
| **Idempotente** | Una operación que produce el mismo resultado si se ejecuta una o N veces. El seeder es idempotente: ejecutarlo dos veces no duplica datos. |
| **Reverse proxy (Nginx)** | Servidor que recibe las peticiones del cliente y las redirige al servicio correcto según la ruta. El cliente solo conoce `localhost:80`; no sabe si está hablando con Laravel o con Vite. |
| **HMR (Hot Module Replacement)** | Funcionalidad de Vite que actualiza el navegador al instante cuando cambia un archivo `.vue`, sin recargar la página completa. Requiere WebSocket, por eso Nginx proxifica también el endpoint `/@vite/`. |
| **firstOrCreate** | Método de Eloquent: busca un registro con los criterios dados; si no existe, lo crea. Garantiza que no haya duplicados. |
| **cast (Eloquent)** | Instrucción al ORM para convertir automáticamente el valor de la BD al tipo PHP indicado. Ejemplo: columna JSON → array PHP. |
