# Capítulo 4 — Análisis y Diseño (Diagramas PlantUML)

Esta carpeta contiene los 10 diagramas PlantUML del capítulo 4 del TFG OmniSim.

## Estructura sugerida en el repo

Colocar estos archivos en `docs/analisisYDiseño/` (o donde decidas organizar la documentación).

## Listado de archivos

### 4.0 Introducción MVC — Inventario por capa
- `4.0_vistas.puml` — 17 vistas Vue agrupadas con composición/navegación (Figura 62)
- `4.0_controladores.puml` — 30 controladores en grid 3×10 (Figura 63)
- `4.0_modelos.puml` — 14 modelos con herencia y relaciones de dominio (Figura 64)

### 4.1 Análisis de casos de uso — Diagramas de colaboración
- `4.1.1_colaboracion_iniciarSesion.puml` — CU-01 (Figura 65)
- `4.1.2_colaboracion_enviarMensaje.puml` — CU-28 (Figura 66)
- `4.1.3_colaboracion_emitirCalificacion.puml` — CU-24 (Figura 67)

### 4.2 Diagrama Entidad-Relación
- `4.2_diagrama_entidad_relacion.puml` — 11 entidades, normalizado 3NF (Figura 68)

### 4.3 Arquitectura lógica
- `4.3_arquitectura_logica.puml` — Cliente SPA + Laravel 4 capas + Orquestador IA (Figura 69)

### 4.4 Diagramas de despliegue
- `4.4.1_despliegue_local.puml` — Entorno desarrollo con hot reload (Figura 70)
- `4.4.2_despliegue_produccion.puml` — Entorno producción con Nginx/HTTPS (Figura 71)

## Convenciones de color

| Tipo | Color fondo | Estereotipo |
|------|------------|-------------|
| Vista | #B3D9FF (azul) | `<<view>>` |
| Controlador | #C8E6A0 (verde) | `<<ctrl>>` |
| Modelo | #FFD9A0 (naranja) | `<<model>>` |
| Actor | #F5F5F5 (gris) | `<<actor>>` |
| Colaboración | #D0F0C0 (verde claro) | `<<collab>>` |
| Package | #FEFECE (amarillo claro) | — |
| Flechas | #A80036 (rojo oscuro) | — |
