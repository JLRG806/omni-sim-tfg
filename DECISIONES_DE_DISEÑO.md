# Plan de trabajo TFG - OmniSim

## Progreso del capítulo 3

### COMPLETADO
- [x] 2.5 Metodología de software (RUP) — redactado
- [x] 3.1 Modelo del dominio — diagrama de clases corregido (asociaciones, etiquetas)
- [x] 3.1.1 Glosario — 6 términos nuevos añadidos
- [x] 3.1.2 Tabla de RF — RF-07 corregido, RF-19 a RF-24 añadidos
- [x] 3.1.2 Diagramas de estados — 3 diagramas corregidos (transiciones + logout)
- [x] 3.2 Actores y CU — lista de 30 CU granulares, actor Orquestador IA añadido
- [x] 3.2.1 Diagrama de actores — corregido con Orquestador IA
- [x] 3.2.2 Diagramas de CU — 4 diagramas por módulo (Sesión, Admin, Profesor, Alumno)
- [x] 3.3 Diagramas de contexto — 3 diagramas (Admin, Profesor, Alumno)

### EN CURSO → 3.4 Detallado de casos de uso
- **Formato:** 30 diagramas individuales + 30 tablas (Actor, Disparador, Flujo, Resultado)
- **Estilo PlantUML:** State diagram con skinparam verde/rojo (estilo del usuario)
- **Estados:** Estandarizados con los diagramas de contexto

#### Lista de 30 CU (numeración definitiva)

**Módulo Transversal:** CU-01 Iniciar Sesión, CU-02 Cerrar Sesión, CU-03 Recuperar Cuenta
**Módulo Administración:** CU-04 Listar Usuarios, CU-05 Crear Usuario, CU-06 Modificar Usuario, CU-07 Eliminar Usuario, CU-08 Buscar Usuario, CU-09 Listar Asignaturas, CU-10 Crear Asignatura, CU-11 Modificar Asignatura, CU-12 Eliminar Asignatura, CU-13 Buscar Asignatura
**Módulo Profesor:** CU-14 Nav Dashboard Profesor, CU-15 Matricular Alumno, CU-16 Desmatricular Alumno, CU-17 Buscar Alumno, CU-18 Crear Escenario, CU-19 Editar Escenario, CU-20 Publicar Escenario, CU-21 Despublicar Escenario, CU-22 Buscar Escenario, CU-23 Revisar Historial, CU-24 Emitir Calificación
**Módulo Alumno:** CU-25 Nav Dashboard Alumno, CU-26 Iniciar Simulación, CU-27 Retomar Simulación, CU-28 Enviar Mensaje, CU-29 Finalizar Sesión, CU-30 Consultar Resultados

#### Estructura de carpetas en el repo (actual)

```
disciplinaDeRequisitos/4. Detallado de casos de uso/
├── Transversal/       → CU-01, CU-02, CU-03
├── Administracion/    → CU-04 a CU-13
├── Profesor/          → CU-14 a CU-24
└── Alumno/            → CU-25 a CU-30
```

#### Progreso detallado
- [x] CU-01 a CU-30 completados (30/30)

#### Decisiones de diseño tomadas durante los detallados
- **CU-18/19**: Dos fases internas (Escenario + PerfilAgente), campos técnicos en diagramas
- **CU-24**: Pre-evaluación IA + revisión profesor (borrador IA → profesor ajusta → publica)
- **CU-29**: Flujo asíncrono (alumno no espera, Orquestador procesa en background)
- **CU-30**: Resultados solo visibles tras validación del profesor
- **Resultados del alumno**: Mapa de descubrimiento (A) + Competencias (B) + Progreso (D) + Feedback profesor (E)
- **Competencias**: 5 fijas universales + personalizables por profesor

### Definición de campos: Escenario + PerfilAgente (multidisciplinar)

El sistema es agnóstico a la disciplina. Los campos usan terminología universal.
Etiquetas en formato pregunta (Opción A) para que cualquier profesor las entienda.

**Fase 1 — Escenario (4 campos):**
1. Título del escenario
2. Área de conocimiento (disciplina)
3. Describe la situación (contexto)
4. ¿Qué debe aprender el alumno? (objetivos)

**Fase 2 — PerfilAgente (7 campos):**
5. ¿Quién es el personaje? (rol/identidad)
6. ¿Cuál es su historia? (trasfondo)
7. ¿Qué sabe y qué no sabe? (conocimientos)
8. ¿Qué dice abiertamente? (información explícita)
9. ¿Qué oculta o cuesta decir? (información latente)
10. ¿Cómo se comunica? (comportamiento/personalidad)
11. Dificultad de la entrevista (Fácil/Medio/Difícil)

**Evaluación (1 campo):**
12. ¿Cómo evalúas al alumno? (criterios de evaluación)

Total: 12 campos. Se traducen en secciones del system prompt para Ollama.
RAG con documentos de referencia → mencionado como trabajo futuro.

**Nivel de dificultad — implementación (A+B combinado):**
El profesor solo elige Fácil/Medio/Difícil. El sistema añade instrucciones automáticas al prompt:
- Fácil: Cooperativo, comparte info, revela info latente con preguntas básicas
- Medio: Natural, info latente solo con buenas preguntas de seguimiento
- Difícil: Evasivo, respuestas vagas, info latente solo con preguntas muy específicas

CU-18 usa un diagrama con 2 fases internas (Escenario + PerfilAgente).
CU-19 también debe permitir editar ambas partes.

### 3.5 Requisitos No Funcionales (13 RNF aprobados)

| ID | Categoría | Requisito |
|---|---|---|
| RNF-01 | Rendimiento | Respuesta de interfaz < 2 seg |
| RNF-02 | Rendimiento | Respuesta Orquestador IA < 15 seg |
| RNF-03 | Disponibilidad | 95% en horario académico (8-22h) |
| RNF-04 | Seguridad | Autenticación JWT (Laravel Sanctum) |
| RNF-05 | Seguridad | Autorización por roles (Admin, Profesor, Alumno) |
| RNF-06 | Seguridad | Contraseñas bcrypt, sesiones con expiración |
| RNF-07 | Usabilidad | Diseño responsive (escritorio + móvil) |
| RNF-08 | Usabilidad | Interfaz intuitiva sin formación previa |
| RNF-09 | Escalabilidad | 50 usuarios concurrentes |
| RNF-10 | Compatibilidad | Chrome, Firefox, Safari, Edge |
| RNF-11 | Mantenibilidad | MVC (Laravel) + componentes (Vue) |
| RNF-12 | Interoperabilidad | API REST + webhooks con n8n |
| RNF-13 | Portabilidad | Despliegue con Docker |

### Stack tecnológico
- **Backend:** Laravel 11 + PHP 8.2
- **Frontend:** Vue 3 + Vite + Tailwind CSS + Pinia + Axios
- **Auth:** Laravel Sanctum (tokens SPA)
- **ORM:** Eloquent
- **BD:** PostgreSQL 16 (soporte nativo de JSON columns para PerfilAgente y Resultados)
- **Cola asíncrona:** Laravel Queue con Database driver (tabla `jobs` en PostgreSQL) — sin contenedor extra
- **Orquestador IA:** n8n + Ollama (agnóstico al proveedor, intercambiable con API externa)
- **Modelo IA:** Por definir (desarrollo local con modelo ligero, demo con API externa)
- **Despliegue:** Docker + Docker Compose + Nginx

### Sección 4.3 — Diseño y arquitectura

**Diferencias clave vs AgrimManager (la referencia):**
- AgrimManager es **multi-página + monolítico**. OmniSim es **SPA + monolito modular + servicio externo**
- AgrimManager: servidor genera HTML/CSS/JS. OmniSim: Vue genera vistas en cliente; Laravel devuelve JSON
- AgrimManager: 3 capas (presentación/negocio/datos). OmniSim: 4 capas (cliente SPA / API / negocio / datos) + orquestador IA externo

**Estructura propuesta de 4.3:**
1. **4.3.1 Decisiones arquitectónicas**: SPA, desacoplado, monolito modular + servicio externo
2. **4.3.2 Arquitectura por capas**: 4 capas + integración con Orquestador IA + figura adaptada
3. **4.3.3 Tecnologías utilizadas**: tabla con stack completo y justificación de cada elección

**Figura 69 — Arquitectura lógica completa (PlantUML):**
- Cliente: navegador con Vue SPA (HTML/CSS/JS renderizado en cliente)
- Servidor Laravel: 4 capas internas (API → Negocio → Datos → BD)
- MySQL como BD
- Servicio externo: Orquestador IA (n8n + Ollama)
- Comunicación: cliente↔servidor por API REST JSON; servidor↔orquestador por REST + webhooks asíncronos
- Equivalente conceptual a la Figura 18 de AgrimManager, adaptada al stack real

**Distinción 4.3 vs 4.4:**
- 4.3 trata arquitectura **lógica** (qué componentes, qué capas, qué tecnologías, cómo se comunican)
- 4.4 trata arquitectura **física/despliegue** (contenedores Docker, red, volúmenes, puertos)
- Docker se menciona brevemente en 4.3 (como decisión arquitectónica, una fila de la tabla de tecnologías) y se desarrolla completamente en 4.4 con el diagrama de despliegue UML

### Sección 4.4 — Diagramas de despliegue

**Enfoque elegido (Opción B):** dos entornos — Local desarrollo + Producción.
CI con GitHub Actions se descarta para evitar pretensión: AgrimManager lo justifica porque su stack es heterogéneo (Tomcat local + Docker DB en dev), OmniSim es 100% Docker en todos los entornos, así que CI no aporta tanta variación visual.

**Estructura:**
- **4.4 intro**: La arquitectura física, dos entornos
- **4.4.1 Despliegue local de desarrollo** — Figura 70
- **4.4.2 Despliegue en producción** — Figura 71

**Contenedores comunes a ambos entornos (6):**
- `omnisim-frontend` (Vue + Nginx en producción / Vite dev server en local)
- `omnisim-backend` (Laravel + PHP-FPM)
- `omnisim-nginx` (proxy reverso del backend, solo en producción como punto de entrada HTTPS)
- `omnisim-postgres` (BD PostgreSQL + tabla `jobs` para cola async)
- `omnisim-n8n` (orquestador workflows)
- `omnisim-ollama` (modelo local IA — opcional en producción si se usa API externa)

**Diferencias entre entornos a reflejar:**

| Aspecto | Local desarrollo | Producción |
|---------|------------------|------------|
| Frontend Vue | Vite dev server (port 5173) con hot reload | Build estático servido por Nginx |
| Backend Laravel | PHP-FPM con APP_DEBUG=true, volumen al código fuente | PHP-FPM con APP_DEBUG=false, imagen Docker construida |
| PostgreSQL | Seeders de datos demo | Backups automáticos |
| Orquestador IA | n8n + Ollama local (modelo ligero, CPU) | n8n local + Ollama o API externa (Groq/OpenRouter) según GPU |
| Acceso externo | localhost:5173 + localhost:8000 | dominio.com con HTTPS via Nginx |
| Volúmenes | Montados a source code (hot reload) | Solo persistencia de datos |

**Elementos a incluir en cada diagrama:**
- Cliente (navegador externo) con estereotipo `<<CLIENT>>`
- Nodo del servidor con estereotipo `<<SERVER>>` (o `<<DEV_MACHINE>>` para local)
- Caja Docker conteniendo los contenedores
- Volúmenes persistentes (postgres_data, n8n_data, ollama_models)
- Red Docker interna
- Conexiones cliente↔servidor (HTTP/HTTPS)
- Flechas internas: frontend↔backend, backend↔postgres, backend↔n8n, n8n↔ollama

**Tabla de tecnologías (a documentar con justificación):**
| Capa | Tecnología | Por qué |
|------|-----------|---------|
| Frontend SPA | Vue 3 + Vite | Reactividad + build rápido |
| Frontend estilos | Tailwind CSS | Utility-first, rápido prototipado |
| Frontend estado | Pinia | Store oficial Vue 3 |
| Frontend HTTP | Axios | Estándar |
| Backend | Laravel 11 + PHP 8.2 | MVC maduro |
| Auth | Laravel Sanctum | Tokens SPA simples |
| ORM | Eloquent | Integrado en Laravel |
| Cola async | Laravel Queue (Database driver) | CU-29 asíncrono — usa tabla `jobs` ya existente, sin contenedor extra |
| BD | PostgreSQL 16 | JSON nativo, tipos avanzados, integrado con Laravel |
| Orquestador IA | n8n + Ollama | Workflows visuales + modelo intercambiable |
| Despliegue | Docker + Compose + Nginx | RNF-13 portabilidad |

### PENDIENTE
- [x] 3.4 Detallado de casos de uso — 30/30 completados
- [x] 3.5 Requisitos no funcionales — 13 RNF aprobados
- [ ] 3.6 Prototipos de interfaz (wireframes baja fidelidad)

### 3.6 Plan de wireframes

**Herramienta:** HTML/CSS generados aquí → usuario abre en navegador → screenshot para doc
**Cantidad:** ~15-20 pantallas (todas)
**Estilo:** Wireframe baja fidelidad (grises, bordes, sin colores ni imágenes)

**Lista de pantallas (20 — análisis exhaustivo CU + diagramas de estados):**
1. Login (CU-01)
2. Recuperar Cuenta (CU-03)
3. Dashboard Admin — Gestión Usuarios (CU-04, CU-08)
4. Formulario Crear/Editar Usuario (CU-05, CU-06)
5. Dashboard Admin — Gestión Asignaturas (CU-09, CU-13)
6. Formulario Crear/Editar Asignatura (CU-10, CU-11)
7. Dashboard Profesor (CU-14)
8. Vista Asignatura — Profesor (hub: escenarios, matrículas, evaluaciones)
9. Gestión Matrículas (CU-15, CU-16, CU-17)
10. Gestión Escenarios (CU-20, CU-21, CU-22)
11. Crear/Editar Escenario — Fase 1 (CU-18, CU-19)
12. Crear/Editar Escenario — Fase 2 Perfil Agente (CU-18, CU-19)
13. Centro de Evaluaciones (CU-23)
14. Emitir Calificación — revisión IA + ajuste (CU-24)
15. Dashboard Alumno (CU-25)
16. Vista Asignatura — Alumno / lobby (CU-26, CU-27)
17. Simulación en Curso / chat (CU-28, CU-29)
18. Resultados — Mapa de Descubrimiento (CU-30)
19. Resultados — Competencias + Progreso (CU-30)
20. Modal de Confirmación — componente (CU-07, CU-12, CU-16, CU-21, CU-29)

### Recopilación de trabajo futuro (para capítulo 6 — Conclusiones)

Decisiones diferidas conscientemente durante el diseño para mantener el alcance del TFG, que pueden mencionarse como líneas de evolución del sistema:

**1. Funcionalidades de IA**
- **RAG con documentos de referencia** en el PerfilAgente — permitiría enriquecer el contexto del agente con materiales subidos por el profesor (PDFs, textos). Requiere n8n + vector store (ChromaDB/Pinecone)
- **Generación asistida del PerfilAgente** — botón "Generar borrador con IA" que rellena los 16 campos a partir de una frase corta del profesor
- **Métricas automáticas en evaluación** (Opción C) — % de preguntas abiertas vs cerradas, cobertura temática objetiva calculada por NLP
- **Sinergia completa de evaluación** (Opciones A+B+C+D) — métricas automáticas + resumen IA + sugerencia + revisión profesor
- **Momentos clave anotados en transcripción** — fragmentos del chat resaltados con feedback contextual

**2. Infraestructura y escalabilidad**
- **Migración a Redis** como driver de cola cuando el sistema escale más allá de 50 usuarios concurrentes
- **Despliegue en cloud con GPU dedicada** (Vast.ai, RunPod) para servir modelos LLM más potentes
- **Modelo IA más potente** (Nemotron 70B, Llama 70B) que la versión local actual

**3. UX del formulario de PerfilAgente**
- **Sub-preguntas guía** dentro de cada campo (lista de viñetas orientando qué escribir)
- **Templates por disciplina** — placeholders y hints adaptativos según el área seleccionada
- **Gallery de avatares predefinidos** — alternativa a subir imagen propia

**4. Gestión y administración**
- **Importación masiva de usuarios** vía CSV (UI) y JSON endpoint (API externa para sistemas de matrícula)
- **Auditoría** (created_by, updated_by) en todas las entidades
- **Operaciones en lote** — selección múltiple para desmatricular alumnos, despublicar escenarios
- **Filtros avanzados** en listados — por estado, profesor, fecha, rendimiento

**5. Funcionalidades pedagógicas**
- **Versionado de escenarios** — historial de cambios al editar un escenario con sesiones activas
- **Comparativa con la media del curso** en la pantalla de resultados (con consideración de UX)
- **Botón "Reintentar escenario"** — sesión adicional sobre el mismo perfil

**6. Plataformas y consumo**
- **App móvil nativa** aprovechando la arquitectura desacoplada (Vue + Laravel API). Posible con Vue Native o reimplementación en React Native/Flutter

### Capítulo 5 — Descripción de la solución propuesta

**Enfoque:** Descripción de la solución diseñada (todavía no implementada).
**Extensión objetivo:** ~5-7 páginas.

**Estructura:**
- **5.1 Visión general de OmniSim** — Qué es, quién lo usa, valor diferenciador multidisciplinar + IA evaluadora
- **5.2 Funcionalidades clave** — 30 CU agrupados por rol. Destacar 3 diferenciadores: simulación con IA, evaluación asistida con revisión humana, mapa de descubrimiento
- **5.3 Materialización del diseño** — Cómo el stack (Vue+Laravel+n8n+Ollama) implementa los RF/RNF. Referencias a capítulos 3 y 4
- **5.4 Estado del desarrollo y hoja de ruta** — Lo diseñado, lo pendiente, calendario aproximado de implementación

### Capítulo 6 — Conclusiones

**Enfoque:** Técnico + reflexión personal.
**Extensión objetivo:** ~4-5 páginas.

**Estructura:**
- **6.1 Conclusiones generales** — Síntesis de la solución y cumplimiento de objetivos del cap. 2
- **6.2 Aportaciones del trabajo** — Diseño completo de 30 CU, 19 wireframes, arquitectura desacoplada con IA agnóstica, sistema multidisciplinar
- **6.3 Limitaciones del estudio** — Tiempo, hardware (sin GPU local), alcance acotado a diseño, ausencia de validación con usuarios reales
- **6.4 Líneas futuras de investigación** — Usar las 6 categorías ya recopiladas en este plan
- **6.5 Reflexión personal** — Aprendizajes durante el TFG: diseño UX/UX, arquitectura distribuida, integración IA, gestión de alcance

### Terminología
Usar siempre **Profesor** (no Docente) y **Alumno** (no Estudiante).

### Workflow del autor
- Primero diagramas PlantUML → commit → luego pegar en Google Doc
- Typo pendiente: digramaDeActores.puml (falta "a")
