<template>
  <div class="min-h-screen bg-gray-100">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">Profesor</span>
        <span class="text-sm text-gray-600">{{ auth.user?.email }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 text-gray-600">Cerrar sesión</button>
      </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">

      <!-- Breadcrumb -->
      <div class="text-xs text-gray-400 mb-4">
        <router-link to="/profesor/dashboard" class="underline hover:text-gray-600">Mis Asignaturas</router-link>
        / {{ asignatura?.nombre }}
      </div>

      <!-- Cabecera asignatura -->
      <div v-if="asignatura" class="bg-white border-2 border-gray-200 rounded-lg px-6 py-5 mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-xl font-semibold text-gray-800 mb-1">{{ asignatura.nombre }}</h1>
          <p class="text-sm text-gray-400">{{ asignatura.descripcion }}</p>
        </div>
        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded font-mono">{{ asignatura.codigo }}</span>
      </div>

      <!-- Tabs -->
      <div class="flex bg-white border-2 border-gray-200 rounded-lg overflow-hidden mb-6">
        <button
          v-for="(tab, i) in tabs" :key="i"
          @click="cambiarTab(i)"
          :class="['flex-1 py-3.5 text-sm font-medium border-r last:border-0 border-gray-100 transition-colors',
            tabActivo === i ? 'bg-gray-100 text-gray-800 font-semibold' : 'text-gray-400 hover:bg-gray-50']"
        >
          {{ tab.label }}
          <span :class="['inline-block ml-1.5 text-xs px-2 py-0.5 rounded-full',
            tabActivo === i ? 'bg-gray-400 text-white' : 'bg-gray-200 text-gray-500']">
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- ── Tab 0: ESCENARIOS ─────────────────────────────────────────────── -->
      <div v-if="tabActivo === 0" class="bg-white border-2 border-gray-200 rounded-lg p-5">
        <div class="flex justify-between items-center mb-5">
          <span class="text-base font-semibold text-gray-600">Escenarios de simulación</span>
          <button @click="crearEscenario" class="bg-gray-400 hover:bg-gray-500 text-white text-sm font-semibold px-4 py-2 rounded-md transition-colors">
            + Crear Escenario
          </button>
        </div>

        <div v-if="loadingEscenarios" class="py-8 text-center text-gray-400 text-sm">Cargando...</div>
        <div v-else-if="escenarios.length === 0" class="py-8 text-center text-gray-400 text-sm">
          No hay escenarios. Crea el primero.
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div v-for="esc in escenarios" :key="esc.id" class="flex justify-between items-center py-3">
            <div>
              <p class="text-sm font-semibold text-gray-800">{{ esc.titulo }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ esc.area_conocimiento }} · {{ dificultadLabel(esc.nivel_dificultad) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span :class="['text-xs font-semibold px-3 py-1 rounded-full',
                esc.estado === 'publicado' ? 'bg-gray-200 text-gray-600' : 'bg-gray-100 text-gray-400']">
                {{ esc.estado === 'publicado' ? 'Publicado' : 'Borrador' }}
              </span>
              <button @click="editarEscenario(esc)" class="text-xs border border-gray-200 px-3 py-1 rounded hover:bg-gray-50 text-gray-500">Editar</button>
              <button v-if="esc.estado === 'publicado'" @click="despublicar(esc)" class="text-xs border border-gray-200 px-3 py-1 rounded hover:bg-gray-50 text-gray-500">Despublicar</button>
              <button v-else @click="publicar(esc)" class="text-xs border border-gray-200 px-3 py-1 rounded hover:bg-gray-50 text-gray-500">Publicar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab 1: MATRÍCULAS ────────────────────────────────────────────── -->
      <div v-if="tabActivo === 1" class="bg-white border-2 border-gray-200 rounded-lg p-5">

        <!-- Matricular nuevo -->
        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-md p-4 mb-5">
          <p class="text-sm font-medium text-gray-500 mb-3">Matricular nuevo alumno</p>
          <div class="relative flex gap-2">
            <input
              v-model="busquedaAlumno"
              @input="buscarAlumnos"
              type="text"
              placeholder="Buscar alumno por nombre o correo..."
              class="flex-1 border-2 border-gray-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-gray-400"
            />
            <button @click="matricularSeleccionado" :disabled="!alumnoSeleccionado"
              class="bg-gray-400 text-white text-sm font-semibold px-4 py-2 rounded-md disabled:opacity-50 hover:bg-gray-500 whitespace-nowrap">
              + Matricular
            </button>
            <!-- Autocomplete -->
            <div v-if="resultadosBusqueda.length && !alumnoSeleccionado"
              class="absolute top-full left-0 right-32 bg-white border-2 border-t-0 border-gray-200 rounded-b-md z-10 max-h-48 overflow-y-auto">
              <div v-for="a in resultadosBusqueda" :key="a.id"
                @click="seleccionarAlumno(a)"
                class="px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-50 cursor-pointer border-b last:border-0 border-gray-100">
                {{ a.name }}
                <span class="block text-xs text-gray-400">{{ a.email }}</span>
              </div>
            </div>
          </div>
          <p v-if="msgMatricula" class="text-xs mt-2" :class="msgMatricula.ok ? 'text-green-600' : 'text-red-500'">{{ msgMatricula.texto }}</p>
        </div>

        <!-- Lista matriculados -->
        <p class="text-sm font-semibold text-gray-600 mb-3">Alumnos matriculados</p>

        <div v-if="loadingMatriculas" class="py-6 text-center text-gray-400 text-sm">Cargando...</div>
        <div v-else-if="alumnosMatriculados.length === 0" class="py-6 text-center text-gray-400 text-sm">No hay alumnos matriculados.</div>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="border-b-2 border-gray-100">
              <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Alumno</th>
              <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Estado</th>
              <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Fecha matrícula</th>
              <th class="py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="a in alumnosMatriculados" :key="a.id" class="hover:bg-gray-50">
              <td class="py-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-500">
                    {{ iniciales(a.name) }}
                  </div>
                  <div>
                    <div class="font-semibold text-gray-700 text-sm">{{ a.name }}</div>
                    <div class="text-xs text-gray-400">{{ a.email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-3"><span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Activo</span></td>
              <td class="py-3 text-xs text-gray-400">{{ a.fecha_matricula }}</td>
              <td class="py-3 text-right">
                <button @click="desmatricular(a)" class="text-xs border border-gray-200 px-3 py-1 rounded hover:bg-gray-50 text-gray-500">Desmatricular</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── Tab 2: EVALUACIONES ──────────────────────────────────────────── -->
      <div v-if="tabActivo === 2">

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3 mb-4">
          <div class="bg-white border-2 border-gray-300 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ statsEval.pendientes }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">Pendientes de evaluar</div>
          </div>
          <div class="bg-white border-2 border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ statsEval.procesando }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">Procesando IA</div>
          </div>
          <div class="bg-white border-2 border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ statsEval.notaMedia ?? '—' }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">Nota media</div>
          </div>
        </div>

        <!-- Filtros + tabla -->
        <div class="bg-white border-2 border-gray-200 rounded-lg p-5">
          <div class="flex gap-2 mb-4 flex-wrap">
            <button v-for="f in filtrosEval" :key="f.valor"
              @click="filtroActivo = f.valor"
              :class="['text-xs px-3 py-1.5 rounded-full border transition-colors',
                filtroActivo === f.valor ? 'bg-gray-600 text-white border-gray-600' : 'border-gray-200 text-gray-500 hover:bg-gray-50']">
              {{ f.label }}
            </button>
          </div>

          <div v-if="loadingEval" class="py-8 text-center text-gray-400 text-sm">Cargando...</div>
          <div v-else-if="sesionesFiltradas.length === 0" class="py-8 text-center text-gray-400 text-sm">No hay sesiones en esta categoría.</div>

          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b-2 border-gray-100">
                <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Alumno</th>
                <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Escenario</th>
                <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Finalizada</th>
                <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Estado</th>
                <th class="text-left py-2 text-xs text-gray-400 font-semibold uppercase tracking-wide">Nota</th>
                <th class="py-2"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="s in sesionesFiltradas" :key="s.id" class="hover:bg-gray-50">
                <td class="py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-500">
                      {{ iniciales(s.alumno?.name ?? '?') }}
                    </div>
                    <div>
                      <div class="font-semibold text-gray-700">{{ s.alumno?.name ?? 'Alumno eliminado' }}</div>
                      <div class="text-xs text-gray-400">{{ s.alumno?.email ?? '' }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 text-gray-600">{{ s.escenario_titulo }}</td>
                <td class="py-3 text-xs text-gray-400">{{ formatFecha(s.finalizacion_at) }}</td>
                <td class="py-3">
                  <span :class="badgeEstado(s.estado)">{{ labelEstado(s.estado) }}</span>
                </td>
                <td class="py-3 font-bold text-gray-600">{{ s.nota ?? '—' }}</td>
                <td class="py-3 text-right">
                  <button v-if="s.estado === 'finalizada'" @click="irACalificar(s)"
                    class="bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold px-3 py-1.5 rounded">
                    Evaluar
                  </button>
                  <button v-else-if="s.estado === 'procesando'"
                    class="text-xs border border-gray-200 px-3 py-1.5 rounded text-gray-400 cursor-default" disabled>
                    Esperando...
                  </button>
                  <button v-else-if="s.estado === 'evaluada'" @click="irACalificar(s)"
                    class="text-xs border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50 text-gray-500">
                    Ver detalle
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
/**
 * VistaAsignaturaProfesorView — WF-08/11/12 — CU-14..24.
 * 3 tabs: Escenarios (WF-08) · Matrículas (WF-11) · Evaluaciones (WF-12).
 * Referencias: docs/prototipos/08_*.html, 11_*.html, 12_*.html
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()
const asigId = Number(route.params.id)

// ── Estado general ────────────────────────────────────────────────────────────
const asignatura = ref(null)
const tabActivo  = ref(0)

const tabs = computed(() => [
  { label: 'Escenarios',   count: escenarios.value.length },
  { label: 'Matrículas',   count: alumnosMatriculados.value.length },
  { label: 'Evaluaciones', count: statsEval.value.pendientes },
])

// ── Tab 0: Escenarios ─────────────────────────────────────────────────────────
const escenarios       = ref([])
const loadingEscenarios = ref(false)

async function cargarEscenarios() {
  loadingEscenarios.value = true
  try {
    const { data } = await api.get(`/asignaturas/${asigId}/escenarios`)
    escenarios.value = data.data ?? []
  } finally {
    loadingEscenarios.value = false
  }
}

function crearEscenario() {
  router.push({ path: '/profesor/escenarios/nuevo', query: { asignatura_id: asigId } })
}
function editarEscenario(esc) {
  router.push(`/profesor/escenarios/${esc.id}/editar`)
}
async function publicar(esc) {
  try {
    await api.patch(`/escenarios/${esc.id}/publicar`)
    esc.estado = 'publicado'
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al publicar')
  }
}
async function despublicar(esc) {
  try {
    await api.patch(`/escenarios/${esc.id}/despublicar`)
    esc.estado = 'borrador'
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al despublicar')
  }
}
function dificultadLabel(d) {
  return { facil: 'Fácil', medio: 'Medio', dificil: 'Difícil' }[d] ?? '—'
}

// ── Tab 1: Matrículas ─────────────────────────────────────────────────────────
const alumnosMatriculados = ref([])
const loadingMatriculas   = ref(false)
const busquedaAlumno      = ref('')
const resultadosBusqueda  = ref([])
const alumnoSeleccionado  = ref(null)
const msgMatricula        = ref(null)

async function cargarMatriculas() {
  loadingMatriculas.value = true
  try {
    const { data } = await api.get(`/asignaturas/${asigId}/alumnos`)
    alumnosMatriculados.value = data.alumnos.filter(a => a.matriculado)
  } finally {
    loadingMatriculas.value = false
  }
}
async function buscarAlumnos() {
  alumnoSeleccionado.value = null
  if (busquedaAlumno.value.length < 2) { resultadosBusqueda.value = []; return }
  try {
    const { data } = await api.get(`/asignaturas/${asigId}/alumnos`, { params: { q: busquedaAlumno.value } })
    resultadosBusqueda.value = data.alumnos.filter(a => !a.matriculado)
  } catch { resultadosBusqueda.value = [] }
}
function seleccionarAlumno(a) {
  alumnoSeleccionado.value = a
  busquedaAlumno.value     = a.name
  resultadosBusqueda.value = []
}
async function matricularSeleccionado() {
  if (!alumnoSeleccionado.value) return
  try {
    await api.post(`/asignaturas/${asigId}/matriculas`, { alumno_id: alumnoSeleccionado.value.id })
    msgMatricula.value = { ok: true, texto: `${alumnoSeleccionado.value.name} matriculado correctamente.` }
    busquedaAlumno.value    = ''
    alumnoSeleccionado.value = null
    await cargarMatriculas()
  } catch (e) {
    msgMatricula.value = { ok: false, texto: e.response?.data?.message ?? 'Error al matricular' }
  }
}
async function desmatricular(alumno) {
  if (!confirm(`¿Desmatricular a ${alumno.name}?`)) return
  try {
    await api.delete(`/matriculas/${alumno.matricula_id}`)
    await cargarMatriculas()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al desmatricular')
  }
}
function iniciales(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

// ── Tab 2: Evaluaciones ───────────────────────────────────────────────────────
const todasSesiones = ref([])
const loadingEval   = ref(false)
const filtroActivo  = ref('todas')

const filtrosEval = [
  { valor: 'todas',     label: 'Todas' },
  { valor: 'finalizada', label: 'Pendientes' },
  { valor: 'procesando', label: 'Procesando IA' },
  { valor: 'evaluada',   label: 'Evaluadas' },
]

async function cargarEvaluaciones() {
  loadingEval.value = true
  try {
    const sesiones = []
    for (const esc of escenarios.value) {
      const { data } = await api.get('/sesiones', { params: { escenario_id: esc.id } })
      for (const s of data.sesiones) {
        sesiones.push({
          ...s,
          escenario_titulo: esc.titulo,
          nota:        s.resultado_nota  ?? null,
          resultado_id: s.resultado_id   ?? null,
        })
      }
    }
    todasSesiones.value = sesiones
  } finally {
    loadingEval.value = false
  }
}

const sesionesFiltradas = computed(() => {
  if (filtroActivo.value === 'todas') return todasSesiones.value
  return todasSesiones.value.filter(s => s.estado === filtroActivo.value)
})

const statsEval = computed(() => {
  const pend = todasSesiones.value.filter(s => s.estado === 'finalizada').length
  const proc = todasSesiones.value.filter(s => s.estado === 'procesando').length
  const eval_ = todasSesiones.value.filter(s => s.estado === 'evaluada')
  const notas = eval_.map(s => s.nota).filter(n => n !== null)
  const media = notas.length ? (notas.reduce((a, b) => a + b, 0) / notas.length).toFixed(1) : null
  return { pendientes: pend, procesando: proc, notaMedia: media }
})

function badgeEstado(estado) {
  const m = {
    finalizada: 'bg-amber-50 text-amber-700 border border-amber-200',
    procesando: 'bg-indigo-50 text-indigo-500 border border-indigo-200',
    evaluada:   'bg-green-50 text-green-700 border border-green-200',
  }
  return `text-xs font-semibold px-2 py-0.5 rounded-full ${m[estado] ?? 'bg-gray-100 text-gray-500'}`
}
function labelEstado(estado) {
  return { finalizada: 'Pendiente', procesando: 'Procesando IA', evaluada: 'Evaluada' }[estado] ?? estado
}
function formatFecha(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  const ahora = new Date()
  const diff = Math.floor((ahora - d) / 60000)
  if (diff < 60) return `Hace ${diff} min`
  if (diff < 1440) return `Hace ${Math.floor(diff / 60)} h`
  if (diff < 2880) return 'Ayer'
  return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}
async function irACalificar(s) {
  // resultado_id viene ya en la respuesta de CU-23
  if (s.resultado_id) {
    router.push(`/profesor/resultados/${s.resultado_id}`)
  }
}

// ── Carga por tab ─────────────────────────────────────────────────────────────
async function cambiarTab(i) {
  tabActivo.value = i
  if (i === 1 && alumnosMatriculados.value.length === 0) await cargarMatriculas()
  if (i === 2 && todasSesiones.value.length === 0)       await cargarEvaluaciones()
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(async () => {
  // Carga asignatura del dashboard
  const { data } = await api.get('/profesor/dashboard')
  asignatura.value = data.asignaturas.find(a => a.id === asigId) ?? null
  await cargarEscenarios()
})
</script>
