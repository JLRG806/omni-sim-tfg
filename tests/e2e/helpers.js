/**
 * Helpers reutilizables para verificación E2E con Playwright.
 * Usado por el skill /verifier-omnisim para verificar CUs en el browser.
 *
 * Requiere: npx playwright (disponible globalmente)
 * App en: http://localhost:8081
 * Usuarios de prueba (creados por DemoSeeder o manualmente):
 *   admin@omnisim.test    / password
 *   profesor@omnisim.test / password
 *   alumno@omnisim.test   / password
 */

const path = require('path')
const fs   = require('fs')

const BASE = 'http://localhost:8081'

const CREDS = {
  admin:    { email: 'admin@omnisim.test',    password: 'password' },
  profesor: { email: 'profesor@omnisim.test', password: 'password' },
  alumno:   { email: 'alumno@omnisim.test',   password: 'password' },
}

/**
 * Inicia sesión como el rol indicado.
 * Espera a que la URL cambie al dashboard correspondiente.
 *
 * @param {import('playwright').Page} page
 * @param {'admin'|'profesor'|'alumno'} rol
 */
async function loginAs(page, rol = 'admin') {
  const cred = CREDS[rol]
  await page.goto(`${BASE}/login`)
  await page.waitForLoadState('networkidle')
  await page.fill('input[type=email]', cred.email)
  await page.fill('input[type=password]', cred.password)
  await page.click('button[type=submit]')
  await page.waitForTimeout(2000)
}

/**
 * Obtiene la cookie CSRF de Sanctum antes de peticiones que la requieren.
 *
 * @param {import('playwright').Page} page
 */
async function getCsrf(page) {
  await page.evaluate(async () => {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  })
}

/**
 * Hace logout limpiando localStorage y recargando para que main.js detecte
 * que no hay token.
 *
 * @param {import('playwright').Page} page
 */
async function logout(page) {
  const token = await page.evaluate(() => localStorage.getItem('omnisim_token'))
  if (token) {
    await page.evaluate(async (tok) => {
      await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: { Authorization: `Bearer ${tok}`, Accept: 'application/json' },
        credentials: 'include',
      })
      localStorage.removeItem('omnisim_token')
    }, token)
    await page.reload({ waitUntil: 'networkidle' })
  }
}

/**
 * Guarda un screenshot en docs/screenshots/{cu}/{nombre}.png
 * Crea el directorio si no existe.
 *
 * @param {import('playwright').Page} page
 * @param {string} cu   — ej. 'CU-05'
 * @param {string} nombre — ej. '01_formulario'
 */
async function ss(page, cu, nombre) {
  const dir = path.join(__dirname, '../../docs/screenshots', cu)
  fs.mkdirSync(dir, { recursive: true })
  await page.screenshot({ path: path.join(dir, `${nombre}.png`) })
}

/**
 * Navega a una URL y espera a que la red esté en reposo.
 *
 * @param {import('playwright').Page} page
 * @param {string} url
 */
async function goto(page, url) {
  await page.goto(`${BASE}${url}`)
  await page.waitForLoadState('networkidle')
}

module.exports = { BASE, loginAs, getCsrf, logout, ss, goto }
