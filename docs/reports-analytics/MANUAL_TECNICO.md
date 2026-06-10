# Manual técnico — Reportes analíticos avanzados

## Arquitectura

```
reports/listing.php          → Hub de navegación (reorganizado, sin cambiar rutas clásicas)
app/Controllers/ReportsAnalytics.php  → 10 reportes × (HTML, PDF, Excel)
app/Models/ReportAnalyticsModel.php   → Consultas SELECT optimizadas
app/Views/reports/analytics/          → Vistas HTML + partials + PDF
public/js/analytics_table.js          → Buscador, orden, paginación (cliente)
app/Commands/ReportsAnalyticsSmoke.php → Validación CLI
```

**Separación:** el controlador `Reports` (clásico) no fue modificado en funcionalidad. Los analíticos viven en `ReportsAnalytics` con modelo propio.

**Permisos:** `moduleId = 'reports'` (mismo que reportes existentes).

## Rutas (`app/Config/Routes.php`)

Cada reporte expone 3 rutas GET:

| Reporte | HTML | PDF | Excel |
|---------|------|-----|-------|
| Pruebas más solicitadas | `pruebasMasSolicitadas` | `…Pdf` | `…Excel` |
| Tendencia paciente | `tendenciaPaciente` | `…Pdf` | `…Excel` |
| Valores críticos | `valoresCriticos` | `…Pdf` | `…Excel` |
| Tiempo entrega | `tiempoEntrega` | `…Pdf` | `…Excel` |
| Productividad usuarios | `productividadUsuarios` | `…Pdf` | `…Excel` |
| Resultados corregidos | `resultadosCorregidos` | `…Pdf` | `…Excel` |
| Pendientes validación | `pendientesValidacion` | `…Pdf` | `…Excel` |
| Consumo insumos | `consumoInsumos` | `…Pdf` | `…Excel` |
| Proyección insumos | `proyeccionInsumos` | `…Pdf` | `…Excel` |
| Comparativo mensual | `comparativoMensual` | `…Pdf` | `…Excel` |

## Tablas involucradas (solo lectura)

| Tabla | Uso |
|-------|-----|
| `dom_registro` | Órdenes, fechas, CSV de pruebas, médico, paciente |
| `dom_regvalues` | Valores de resultados (EAV: `c_*`, `noc_*`) |
| `dom_resulanalisis` | Flags de validación técnica/médica |
| `dom_auditoria` | TAT, productividad, correcciones |
| `dom_people` | Pacientes y empleados |
| `dom_employees` | Usuarios del sistema |
| `dom_doctors` | Médicos solicitantes |
| `dom_anacategoria` / `dom_prianacategoria` / `dom_secanacategoria` / `dom_priresultados` | Catálogo y referencias |
| `dom_pago` | Facturación comparativo mensual |
| `dom_reactivo` / `dom_reactivo_lote` / `dom_reactivo_movimiento` / `dom_reactivo_consumo_auto` | Inventario y consumo |

**No se crearon ni alteraron tablas** para estos reportes.

## Índices utilizados

Migración `2026-04-01-200000_DbOptimizeIndexesLaboratorio`:

- `idx_registro_ingreso`, `idx_registro_person_ingreso`, `idx_registro_doctor_ingreso`, `idx_registro_anulado_ingreso`
- `idx_regvalues_registro_ord`
- `idx_auditoria_modulo_fecha`, `idx_auditoria_fecha`, `idx_auditoria_person_id`
- `idx_prianacategoria_anacat_deleted`, `idx_secanacategoria_pria_deleted`, `idx_priresultados_pria_pob_del`
- `idx_pago_registro_id`, `idx_resulanalisis_registro_id`

## Detalle por reporte

### 1. Pruebas más solicitadas

- **Método:** `ReportAnalyticsModel::getPruebasMasSolicitadas()`
- **Estrategia:** una consulta SELECT de órdenes en rango; agregación del CSV `pruebas` en PHP O(n).
- **Ingreso:** `cantidad × prianacategoria.cost`
- **Límite sucursal:** no aplica (sin tabla de sucursales).

### 2. Tendencia histórica por paciente

- **Método:** `getTendenciaPaciente($personId, $start, $end, $pruebaId, $limit=2000)`
- **SQL:** UNION de joins `regvalues` → catálogo (complejas y simples).
- **Estado:** comparación numérica vs `valor_min/max` y `critico_min/max`.

### 3. Valores críticos

- **Método:** `getValoresCriticos()` — reutiliza `buildRegvaluesResolvedSql()`.
- **Límite detalle:** 3000 filas; agregados por prueba/grupo en SQL.

### 4. Tiempo de entrega (TAT)

- **Métodos:** `getTiempoEntrega()`, `getTiempoEntregaResumen()`
- **Eventos:** `guardar_resultados` (primer resultado), `validar_tecnico`/`validar_medico` (validación).
- **SLA:** parámetro GET `sla` (horas).

### 5. Productividad por usuario

- **Método:** `getProductividadUsuarios()`
- **Fuentes:** `registro.id_session` (recepciones) + agregación `auditoria` por `person_id` y `accion`.

### 6. Resultados corregidos

- **Método:** `getResultadosCorregidos()`
- **Filtro JSON:** `JSON_LENGTH(JSON_EXTRACT(datos, '$.cambios')) > 0`
- **Trazabilidad:** usa auditoría existente; no modifica registros históricos.

### 7. Pendientes de validación

- **Método:** `getPendientesValidacion()`
- **Criterio:** tiene `regvalues`, sin evento de validación en `auditoria` ni flags en `resulanalisis`.

### 8. Consumo de insumos

- **Métodos:** `getConsumoPorPrueba()`, `getConsumoPorReactivo()`, `getConsumoPorPeriodo()`
- **Fuentes:** `reactivo_consumo_auto` (aplicado) y `reactivo_movimiento` (salida).

### 9. Proyección de agotamiento

- **Método:** `getProyeccionInsumos($diasVentana, $diasCritico, $diasAdvertencia)`
- **Cálculo:** `stock / (consumo_total / dias_ventana)` → días restantes y fecha estimada.

### 10. Comparativo mensual

- **Método:** `getComparativoMensual()`
- **Agregación:** `DATE_FORMAT(ingreso, '%Y-%m')` con LEFT JOIN `pago`.
- **Variaciones:** calculadas en PHP (mes anterior y mismo mes año anterior).

## Exportación

- **PDF:** `ReportPdfDocument::download()` → Dompdf vía `PdfService`.
- **Excel:** CSV UTF-8 con BOM (`streamCsv()` en controlador).

## Rendimiento

- Objetivo: &lt; 5 s para 100.000 registros.
- Validación: `tests/perf/analytics_perf_seed.sql` crea BD `laboratorio_perftest` aislada.
- Smoke test: `php spark reports:analytics-smoke [start] [end] [bd]`
- Límites defensivos en detalle: 2000–3000 filas (paginación cliente sobre subconjunto).

## Extensibilidad

Para agregar un reporte analítico nuevo:

1. Añadir método en `ReportAnalyticsModel` (solo SELECT).
2. Añadir métodos HTML/PDF/Excel en `ReportsAnalytics`.
3. Crear vista en `app/Views/reports/analytics/`.
4. Registrar 3 rutas en `Routes.php`.
5. Enlazar en `listing.php`.

No se requiere modificar `Reports`, modelos de registro, facturación ni inventario.
