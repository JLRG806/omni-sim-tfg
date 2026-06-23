---
name: verifier-omnisim
description: Verifica un CU de OmniSim en el browser usando Playwright. Usa los helpers de tests/e2e/helpers.js, guarda screenshots en docs/screenshots/CU-XX/ y reporta en el formato estándar de verify. Uso: /verifier-omnisim CU-05
---

# verifier-omnisim

Verifica un Caso de Uso de OmniSim corriendo la app real en `localhost:8081`.

## Uso
```
/verifier-omnisim CU-05
/verifier-omnisim CU-01
```

---

## Setup previo (verificar antes de ejecutar)

```bash
# App corriendo
curl -s -o /dev/null -w "%{http_code}" http://localhost:8081
# debe devolver 200

# Playwright disponible
npx playwright --version

# Usuarios de prueba en la BD (si no existen, crear con PHP PDO o tinker)
# admin@omnisim.test / password  (rol: admin)
# profesor@omnisim.test / password  (rol: profesor)
# alumno@omnisim.test / password  (rol: alumno)
```

---

## Workflow

### 1. Identificar la superficie del CU

| CU | Superficie | Método |
|---|---|---|
| Auth (01-03) | `POST /api/v1/auth/*` + LoginView | curl + Playwright |
| Admin (04-13) | `GET/POST/PUT/DELETE /api/v1/usuarios\|asignaturas` + vistas admin | curl + Playwright |
| Profesor (14-24) | endpoints profesor + vistas profesor | curl + Playwright |
| Alumno (25-30) | endpoints alumno + SimulacionChatView | curl + Playwright |

### 2. Script de verificación

Crear el script en memoria (no guardarlo permanentemente) usando los helpers:

```js
const { chromium } = require('playwright')
const { loginAs, getCsrf, logout, ss, goto, BASE } = require('./tests/e2e/helpers.js')

;(async () => {
  const browser = await chromium.launch({ headless: true })
  const page    = await browser.newPage()

  // ── Pasos de verificación ─────────────────────────────────────────────────

  // 1. Descripción del paso
  await loginAs(page, 'admin')
  await ss(page, 'CU-XX', '01_descripcion')
  console.log('1. URL:', page.url())

  // 2. Acción específica del CU
  await goto(page, '/ruta/del/cu')
  await ss(page, 'CU-XX', '02_vista_cargada')
  console.log('2. Título:', await page.locator('h1').textContent())

  // ── Probes (al menos uno) ─────────────────────────────────────────────────

  // 3. Probe: escenario de error o flujo alternativo
  await getCsrf(page)
  // ... acción que debería fallar
  const error = await page.locator('p.text-red-500').textContent().catch(() => 'sin error')
  console.log('3. Error probe:', error)

  await browser.close()
})()
```

### 3. Ejecutar

```bash
cd /home/jlrg/Documents/UNEATLANTICO/TFG/omni-sim-tfg
node -e "$(cat <<'SCRIPT'
  ... script inline ...
SCRIPT
)"
```

O escribir a un archivo temporal y ejecutar:
```bash
node /tmp/verify-cu-XX.js
```

### 4. Verificación API complementaria (curl)

Para endpoints REST, siempre verificar también con curl:

```bash
# Login para obtener token
TOKEN=$(curl -s -X POST http://localhost:8081/api/v1/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@omnisim.test","password":"password"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")

# Llamada al endpoint del CU
curl -s http://localhost:8081/api/v1/RUTA \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool
```

---

## Screenshots

Los screenshots se guardan en `docs/screenshots/CU-XX/` automáticamente cuando se usa el helper `ss(page, 'CU-XX', 'nombre')`.

Convención de nombres:
- `01_estado_inicial.png` — cómo se ve antes de actuar
- `02_flujo_principal.png` — resultado del happy path
- `03_error_probe.png` — resultado de un flujo alternativo

---

## Reporte

Usar el formato estándar del skill `/verify`:

```
## Verification: CU-XX — descripción

**Verdict:** PASS | FAIL | BLOCKED

**Claim:** qué debe hacer el CU

**Method:** Playwright headless + curl contra localhost:8081

### Steps
1. ✅ acción → resultado observado
2. ✅ acción → resultado observado
3. 🔍 probe → resultado observado

### Findings
- ⚠️ hallazgos importantes
- bullet hallazgos menores
```

---

## Usuarios de prueba — crear si no existen

```php
// Via PHP PDO (directo a PostgreSQL en :5432)
$pdo  = new PDO('pgsql:host=localhost;port=5432;dbname=omnisim', 'omnisim', 'omnisim_secret');
$hash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, rol, estado, email_verified_at, created_at, updated_at)
  VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW()) ON CONFLICT (email) DO NOTHING');
$stmt->execute(['Admin Test',    'admin@omnisim.test',    $hash, 'admin',    'activo']);
$stmt->execute(['Profesor Test', 'profesor@omnisim.test', $hash, 'profesor', 'activo']);
$stmt->execute(['Alumno Test',   'alumno@omnisim.test',   $hash, 'alumno',   'activo']);
```

O via tinker dentro del contenedor:
```bash
sudo docker compose -f docker-compose.dev.yml exec backend php artisan tinker
```
